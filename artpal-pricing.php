<?php
/**
 * Tools → ArtPal Prices.
 *
 * Raise unsold artpal_price or artpal_shipping in tiers: a price range,
 * a percent or dollar increase, then an optional round-up. Preview lists
 * every change before anything is saved. The last run can be undone.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARTPAL_PRICING_RULE_ROWS', 6 );
define( 'ARTPAL_LAST_PRICE_RUN', 'artpal_last_price_run' );

/**
 * Blank, or not a number, or a float.
 *
 * @param mixed $raw Posted value.
 * @return float|null|false Null when blank, false when not a number.
 */
function artpal_pricing_parse_number( $raw ) {
	$raw = trim( str_replace( array( '$', ',', ' ', '%' ), '', (string) $raw ) );
	if ( $raw === '' ) {
		return null;
	}
	if ( ! is_numeric( $raw ) ) {
		return false;
	}
	return (float) $raw;
}

/**
 * @param mixed $raw Posted value.
 * @return bool
 */
function artpal_pricing_raw_blank( $raw ) {
	return trim( (string) $raw ) === '';
}

/**
 * Whole cents, half-up, so 19.99 and 8.70 do not drift.
 *
 * @param mixed $amount Dollar amount.
 * @return int
 */
function artpal_pricing_to_cents( $amount ) {
	return (int) round( (float) $amount * 100 );
}

/**
 * Six form rows, with blanks for anything the request left out.
 *
 * @param mixed $posted artpal_rules from the request.
 * @return array<int,array<string,string>>
 */
function artpal_pricing_request_rows( $posted ) {
	$posted = is_array( $posted ) ? array_values( $posted ) : array();
	$rows   = array();
	for ( $i = 0; $i < ARTPAL_PRICING_RULE_ROWS; $i++ ) {
		$row  = ( isset( $posted[ $i ] ) && is_array( $posted[ $i ] ) ) ? $posted[ $i ] : array();
		$mode = ( isset( $row['mode'] ) && $row['mode'] === 'flat' ) ? 'flat' : 'percent';
		$rows[] = array(
			'min'    => isset( $row['min'] ) ? trim( (string) $row['min'] ) : '',
			'max'    => isset( $row['max'] ) ? trim( (string) $row['max'] ) : '',
			'amount' => isset( $row['amount'] ) ? trim( (string) $row['amount'] ) : '',
			'mode'   => $mode,
			'round'  => isset( $row['round'] ) ? trim( (string) $row['round'] ) : '',
		);
	}
	return $rows;
}

/**
 * Validate tier rows.
 *
 * Ranges are half-open: From is included, Up to is not. A blank Up to has no
 * upper limit. Blank rows are ignored. Overlapping rows are an error, so a
 * price is raised by one row only.
 *
 * @param mixed $raw_rows Posted rows.
 * @return array{rules: array<int,array>, errors: string[]}
 */
function artpal_pricing_parse_rules( $raw_rows ) {
	$errors = array();
	$rules  = array();

	if ( ! is_array( $raw_rows ) ) {
		$raw_rows = array();
	}
	$raw_rows = array_slice( array_values( $raw_rows ), 0, ARTPAL_PRICING_RULE_ROWS );

	foreach ( $raw_rows as $index => $row ) {
		$line = $index + 1;
		if ( ! is_array( $row ) ) {
			continue;
		}

		$min_raw    = isset( $row['min'] ) ? $row['min'] : '';
		$max_raw    = isset( $row['max'] ) ? $row['max'] : '';
		$amount_raw = isset( $row['amount'] ) ? $row['amount'] : '';
		$round_raw  = isset( $row['round'] ) ? $row['round'] : '';
		$mode       = isset( $row['mode'] ) ? (string) $row['mode'] : 'percent';

		if (
			artpal_pricing_raw_blank( $min_raw )
			&& artpal_pricing_raw_blank( $max_raw )
			&& artpal_pricing_raw_blank( $amount_raw )
			&& artpal_pricing_raw_blank( $round_raw )
		) {
			continue;
		}

		$row_errors = array();

		if ( artpal_pricing_raw_blank( $min_raw ) ) {
			$min = 0.0;
		} else {
			$min = artpal_pricing_parse_number( $min_raw );
			if ( $min === false || $min === null ) {
				$row_errors[] = 'Row ' . $line . ': From must be a number.';
			} elseif ( $min < 0 ) {
				$row_errors[] = 'Row ' . $line . ': From cannot be negative.';
			}
		}

		if ( artpal_pricing_raw_blank( $max_raw ) ) {
			$max = null;
		} else {
			$max = artpal_pricing_parse_number( $max_raw );
			if ( $max === false || $max === null ) {
				$row_errors[] = 'Row ' . $line . ': Up to must be a number.';
			} elseif ( $max < 0 ) {
				$row_errors[] = 'Row ' . $line . ': Up to cannot be negative.';
			}
		}

		if ( artpal_pricing_raw_blank( $amount_raw ) ) {
			$amount       = null;
			$row_errors[] = 'Row ' . $line . ': Enter an increase. Use 0 to only round up.';
		} else {
			$amount = artpal_pricing_parse_number( $amount_raw );
			if ( $amount === false || $amount === null ) {
				$row_errors[] = 'Row ' . $line . ': Increase must be a number.';
			} elseif ( $amount < 0 ) {
				$row_errors[] = 'Row ' . $line . ': Increase cannot be negative.';
			}
		}

		if ( artpal_pricing_raw_blank( $round_raw ) ) {
			$round = 0.0;
		} else {
			$round = artpal_pricing_parse_number( $round_raw );
			if ( $round === false || $round === null ) {
				$row_errors[] = 'Row ' . $line . ': Round up to must be a number.';
			} elseif ( $round < 0 ) {
				$row_errors[] = 'Row ' . $line . ': Round up to cannot be negative.';
			}
		}

		if ( $mode !== 'percent' && $mode !== 'flat' ) {
			$row_errors[] = 'Row ' . $line . ': Choose percent or a dollar amount.';
		} elseif ( $mode === 'percent' && is_float( $amount ) && $amount > 10000 ) {
			$row_errors[] = 'Row ' . $line . ': Percent increase must be 10000 or less.';
		} elseif ( $mode === 'flat' && is_float( $amount ) && $amount > 1000000 ) {
			$row_errors[] = 'Row ' . $line . ': Dollar increase must be 1000000 or less.';
		}

		if ( ! empty( $row_errors ) ) {
			$errors = array_merge( $errors, $row_errors );
			continue;
		}

		$min_cents = artpal_pricing_to_cents( $min );
		$max_cents = ( null === $max ) ? null : artpal_pricing_to_cents( $max );
		if ( null !== $max_cents && $max_cents <= $min_cents ) {
			$errors[] = 'Row ' . $line . ': Up to must be higher than From.';
			continue;
		}

		$rules[] = array(
			'min_cents'    => $min_cents,
			'max_cents'    => $max_cents,
			'mode'         => $mode,
			'amount_cents' => ( 'flat' === $mode ) ? artpal_pricing_to_cents( $amount ) : 0,
			'percent_bp'   => ( 'percent' === $mode ) ? (int) round( $amount * 100 ) : 0,
			'round_cents'  => artpal_pricing_to_cents( $round ),
			'row'          => $line,
		);
	}

	if ( empty( $rules ) && empty( $errors ) ) {
		$errors[] = 'Enter at least one price range.';
	}

	if ( empty( $errors ) ) {
		usort(
			$rules,
			function ( $a, $b ) {
				if ( $a['min_cents'] === $b['min_cents'] ) {
					return $a['row'] <=> $b['row'];
				}
				return $a['min_cents'] <=> $b['min_cents'];
			}
		);
		$count = count( $rules );
		for ( $i = 0; $i < $count; $i++ ) {
			for ( $j = $i + 1; $j < $count; $j++ ) {
				if ( artpal_pricing_ranges_overlap( $rules[ $i ], $rules[ $j ] ) ) {
					$errors[] = 'Rows ' . $rules[ $i ]['row'] . ' and ' . $rules[ $j ]['row'] . ' cover some of the same prices. Each price should fall in only one row.';
				}
			}
		}
	}

	if ( ! empty( $errors ) ) {
		return array(
			'rules'  => array(),
			'errors' => $errors,
		);
	}

	return array(
		'rules'  => $rules,
		'errors' => array(),
	);
}

/**
 * Half-open ranges overlap when they share a price.
 *
 * @param array $a Rule.
 * @param array $b Rule.
 * @return bool
 */
function artpal_pricing_ranges_overlap( $a, $b ) {
	$a_max = ( null === $a['max_cents'] ) ? PHP_INT_MAX : (int) $a['max_cents'];
	$b_max = ( null === $b['max_cents'] ) ? PHP_INT_MAX : (int) $b['max_cents'];
	return (int) $a['min_cents'] < $b_max && (int) $b['min_cents'] < $a_max;
}

/**
 * First tier that contains this price, or null.
 *
 * @param int   $cents Price in cents.
 * @param array $rules Parsed rules.
 * @return array|null
 */
function artpal_pricing_match_rule( $cents, $rules ) {
	$cents = (int) $cents;
	foreach ( $rules as $rule ) {
		if ( $cents < (int) $rule['min_cents'] ) {
			continue;
		}
		if ( null !== $rule['max_cents'] && $cents >= (int) $rule['max_cents'] ) {
			continue;
		}
		return $rule;
	}
	return null;
}

/**
 * Increase, then round up to the nearest step. The result is never lower.
 *
 * A blank step leaves the increased amount at whole cents. Percent math stays
 * in integer cents so $1,000 + 20% lands on $1,200 and not a cent over.
 *
 * @param int   $cents Current amount in cents.
 * @param array $rule  Parsed rule.
 * @return int
 */
function artpal_pricing_adjust_cents( $cents, $rule ) {
	$cents    = (int) $cents;
	$original = $cents;

	if ( isset( $rule['mode'] ) && $rule['mode'] === 'percent' ) {
		$bp = isset( $rule['percent_bp'] ) ? (int) $rule['percent_bp'] : 0;
		if ( $bp > 0 && $cents > 0 ) {
			$factor = 10000 + $bp;
			if ( $factor <= intdiv( PHP_INT_MAX - 5000, $cents ) ) {
				$cents = intdiv( $cents * $factor + 5000, 10000 );
			}
		}
	} else {
		$add = isset( $rule['amount_cents'] ) ? (int) $rule['amount_cents'] : 0;
		if ( $add > 0 ) {
			$cents += $add;
		}
	}

	$step = isset( $rule['round_cents'] ) ? (int) $rule['round_cents'] : 0;
	if ( $step > 1 ) {
		$rem = $cents % $step;
		if ( $rem !== 0 ) {
			$cents += $step - $rem;
		}
	}

	if ( $cents < $original ) {
		return $original;
	}
	return $cents;
}

/**
 * @param float|int $amount Dollar amount.
 * @param array     $rule   Parsed rule.
 * @return float
 */
function artpal_pricing_adjust( $amount, $rule ) {
	return artpal_pricing_adjust_cents( artpal_pricing_to_cents( $amount ), $rule ) / 100;
}

/**
 * @param string $field price or shipping.
 * @return string Meta key.
 */
function artpal_pricing_meta_key( $field ) {
	if ( 'shipping' === $field ) {
		return ds_ap_CFSHIPPING;
	}
	return ds_ap_CFPRICE;
}

/**
 * Stored amount in cents.
 *
 * Null means the meta is missing. False means it is not a usable number.
 * Missing shipping is free (zero) to the rest of ArtPal; the caller decides.
 *
 * @param int    $post_id Post ID.
 * @param string $field   price or shipping.
 * @return int|null|false
 */
function artpal_pricing_stored_cents( $post_id, $field ) {
	$raw = get_post_meta( (int) $post_id, artpal_pricing_meta_key( $field ), true );
	if ( $raw === '' || $raw === null || false === $raw ) {
		return null;
	}
	if ( ! is_numeric( $raw ) ) {
		return false;
	}
	$cents = artpal_pricing_to_cents( $raw );
	if ( $cents < 0 ) {
		return false;
	}
	return $cents;
}

/**
 * Unsold posts: Available, and not Sold. Drafts and private posts count.
 * Trashed posts do not. artpal_is_available() confirms each id.
 *
 * @return int[]
 */
function artpal_unsold_post_ids() {
	$available = (int) get_option( 'ds_ap_unsoldcategory' );
	if ( $available <= 0 ) {
		return array();
	}

	$sold      = (int) get_option( 'ds_ap_soldcategory' );
	$tax_query = array(
		'relation' => 'AND',
		array(
			'taxonomy'         => 'category',
			'field'            => 'term_id',
			'terms'            => array( $available ),
			'include_children' => false,
		),
	);
	if ( $sold > 0 ) {
		$tax_query[] = array(
			'taxonomy'         => 'category',
			'field'            => 'term_id',
			'terms'            => array( $sold ),
			'operator'         => 'NOT IN',
			'include_children' => false,
		);
	}

	$posts = get_posts(
		array(
			'post_type'           => 'post',
			'post_status'         => array( 'publish', 'pending', 'draft', 'future', 'private' ),
			'posts_per_page'      => -1,
			'fields'              => 'ids',
			'tax_query'           => $tax_query,
			'orderby'             => 'ID',
			'order'               => 'ASC',
			'no_found_rows'       => true,
			'suppress_filters'    => true,
		)
	);
	if ( ! is_array( $posts ) ) {
		return array();
	}

	$ids = array();
	foreach ( $posts as $post_id ) {
		$post_id = (int) $post_id;
		if ( artpal_is_available( $post_id ) ) {
			$ids[] = $post_id;
		}
	}
	return $ids;
}

/**
 * @param int $post_id Post ID.
 * @return string
 */
function artpal_pricing_post_status( $post_id ) {
	$post = get_post( $post_id );
	if ( is_object( $post ) && isset( $post->post_status ) ) {
		return (string) $post->post_status;
	}
	if ( is_array( $post ) && isset( $post['post_status'] ) ) {
		return (string) $post['post_status'];
	}
	return 'publish';
}

/**
 * Build the change list from current meta. Each item is matched once, on its
 * current amount, so a price that moves into the next tier is not raised twice.
 *
 * @param string $field        price or shipping.
 * @param array  $rules        Parsed rules.
 * @param bool   $include_free When false, blank or zero shipping stays free.
 * @return array
 */
function artpal_pricing_build_plan( $field, $rules, $include_free = false ) {
	$field        = ( 'shipping' === $field ) ? 'shipping' : 'price';
	$include_free = (bool) $include_free;
	$rules        = is_array( $rules ) ? $rules : array();

	$rows          = array();
	$unchanged     = 0;
	$no_value      = 0;
	$skipped_free  = 0;
	$total_before  = 0;
	$total_after   = 0;

	foreach ( artpal_unsold_post_ids() as $post_id ) {
		$cents = artpal_pricing_stored_cents( $post_id, $field );
		if ( false === $cents ) {
			$no_value++;
			continue;
		}
		if ( 'price' === $field && null === $cents ) {
			$no_value++;
			continue;
		}
		if ( 'shipping' === $field && ( null === $cents || 0 === $cents ) && ! $include_free ) {
			$skipped_free++;
			continue;
		}
		if ( null === $cents ) {
			$cents = 0;
		}

		$rule = artpal_pricing_match_rule( $cents, $rules );
		if ( null === $rule ) {
			$unchanged++;
			continue;
		}

		$new_cents = artpal_pricing_adjust_cents( $cents, $rule );
		if ( $new_cents === $cents ) {
			$unchanged++;
			continue;
		}

		$title = get_the_title( $post_id );
		if ( ! is_string( $title ) || $title === '' ) {
			$title = '(no title)';
		}

		$rows[] = array(
			'post_id'      => (int) $post_id,
			'title'        => $title,
			'status_label' => artpal_inventory_label( $post_id ),
			'post_status'  => artpal_pricing_post_status( $post_id ),
			'old'          => $cents / 100,
			'new'          => $new_cents / 100,
			'diff'         => ( $new_cents - $cents ) / 100,
		);
		$total_before += $cents;
		$total_after  += $new_cents;
	}

	usort(
		$rows,
		function ( $a, $b ) {
			$ao = artpal_pricing_to_cents( $a['old'] );
			$bo = artpal_pricing_to_cents( $b['old'] );
			if ( $ao === $bo ) {
				return $a['post_id'] <=> $b['post_id'];
			}
			return $ao <=> $bo;
		}
	);

	$parts = array();
	foreach ( $rows as $row ) {
		$parts[] = $row['post_id'] . ':' . number_format( $row['old'], 2, '.', '' ) . ':' . number_format( $row['new'], 2, '.', '' );
	}

	return array(
		'field'         => $field,
		'rows'          => $rows,
		'changed'       => count( $rows ),
		'unchanged'     => $unchanged,
		'no_value'      => $no_value,
		'skipped_free'  => $skipped_free,
		'total_before'  => $total_before / 100,
		'total_after'   => $total_after / 100,
		'fingerprint'   => md5( implode( '|', $parts ) ),
	);
}

/**
 * @param array  $plan        Plan from artpal_pricing_build_plan().
 * @param string $fingerprint Fingerprint the preview showed.
 * @return bool
 */
function artpal_pricing_fingerprints_match( $plan, $fingerprint ) {
	$expected    = ( isset( $plan['fingerprint'] ) ) ? (string) $plan['fingerprint'] : '';
	$fingerprint = (string) $fingerprint;
	if ( $expected === '' || strlen( $expected ) !== strlen( $fingerprint ) ) {
		return false;
	}
	return hash_equals( $expected, $fingerprint );
}

/**
 * Save the plan. Refuses posts that are no longer unsold. An empty plan does
 * not replace the undo snapshot.
 *
 * @param array $plan Plan from artpal_pricing_build_plan().
 * @return array|null Stored run, or null when nothing was written.
 */
function artpal_pricing_commit_plan( $plan ) {
	if ( empty( $plan['rows'] ) || ! is_array( $plan['rows'] ) ) {
		return null;
	}

	$field  = ( isset( $plan['field'] ) && $plan['field'] === 'shipping' ) ? 'shipping' : 'price';
	$key    = artpal_pricing_meta_key( $field );
	$stored = array();

	foreach ( $plan['rows'] as $row ) {
		if ( ! is_array( $row ) || ! isset( $row['post_id'], $row['old'], $row['new'] ) ) {
			continue;
		}
		$post_id = (int) $row['post_id'];
		if ( $post_id <= 0 || ! get_post( $post_id ) || ! artpal_is_available( $post_id ) ) {
			continue;
		}
		$new = number_format( (float) $row['new'], 2, '.', '' );
		update_post_meta( $post_id, $key, $new );
		$stored[] = array(
			'post_id' => $post_id,
			'old'     => number_format( (float) $row['old'], 2, '.', '' ),
			'new'     => $new,
		);
	}

	if ( empty( $stored ) ) {
		return null;
	}

	$record = array(
		'at'     => gmdate( 'c' ),
		'field'  => $field,
		'rows'   => $stored,
		'totals' => array(
			'changed'      => count( $stored ),
			'total_before' => isset( $plan['total_before'] ) ? $plan['total_before'] : 0,
			'total_after'  => isset( $plan['total_after'] ) ? $plan['total_after'] : 0,
		),
	);
	update_option( ARTPAL_LAST_PRICE_RUN, $record, false );
	return $record;
}

/**
 * Rebuild the plan and save it only when it still matches the preview.
 *
 * @param string $field        price or shipping.
 * @param array  $rules        Parsed rules.
 * @param bool   $include_free Include free shipping.
 * @param string $fingerprint  Fingerprint from the preview.
 * @return array{ok: bool, plan: array, record: array|null}
 */
function artpal_pricing_apply( $field, $rules, $include_free, $fingerprint ) {
	$plan = artpal_pricing_build_plan( $field, $rules, $include_free );
	if ( ! artpal_pricing_fingerprints_match( $plan, $fingerprint ) ) {
		return array(
			'ok'     => false,
			'plan'   => $plan,
			'record' => null,
		);
	}
	return array(
		'ok'     => true,
		'plan'   => $plan,
		'record' => artpal_pricing_commit_plan( $plan ),
	);
}

/**
 * Restore the last run where the current amount is still the increased one.
 * A price edited afterward is left alone. The snapshot is then removed.
 *
 * @return array{restored: int, skipped: int, missing: bool}
 */
function artpal_pricing_undo_last_run() {
	$run = get_option( ARTPAL_LAST_PRICE_RUN, array() );
	if ( ! is_array( $run ) || empty( $run['rows'] ) || ! is_array( $run['rows'] ) ) {
		return array(
			'restored' => 0,
			'skipped'  => 0,
			'missing'  => true,
		);
	}

	$field    = ( isset( $run['field'] ) && $run['field'] === 'shipping' ) ? 'shipping' : 'price';
	$key      = artpal_pricing_meta_key( $field );
	$restored = 0;
	$skipped  = 0;

	foreach ( $run['rows'] as $row ) {
		if ( ! is_array( $row ) || ! isset( $row['post_id'], $row['old'], $row['new'] ) ) {
			$skipped++;
			continue;
		}
		$post_id  = (int) $row['post_id'];
		$current  = artpal_pricing_stored_cents( $post_id, $field );
		$expected = artpal_pricing_to_cents( $row['new'] );
		if ( null === $current || false === $current || $current !== $expected ) {
			$skipped++;
			continue;
		}
		update_post_meta( $post_id, $key, number_format( (float) $row['old'], 2, '.', '' ) );
		$restored++;
	}

	delete_option( ARTPAL_LAST_PRICE_RUN );
	return array(
		'restored' => $restored,
		'skipped'  => $skipped,
		'missing'  => false,
	);
}

/**
 * @param float|int $amount Dollar amount.
 * @return string
 */
function artpal_pricing_format_money( $amount ) {
	$symbol = html_entity_decode( (string) get_option( 'ds_ap_currencysymbol' ), ENT_QUOTES, 'UTF-8' );
	if ( $symbol === '' ) {
		$symbol = '$';
	}
	$negative = ( (float) $amount < 0 );
	$number   = number_format( abs( (float) $amount ), 2, '.', ',' );
	return ( $negative ? '-' : '' ) . $symbol . $number;
}

/**
 * @param float|int $amount Dollar difference.
 * @return string
 */
function artpal_pricing_format_diff( $amount ) {
	$text = artpal_pricing_format_money( $amount );
	if ( (float) $amount > 0 ) {
		return '+' . $text;
	}
	return $text;
}

/**
 * @param float|int $old Previous amount.
 * @param float|int $new New amount.
 * @return string Empty when there is no baseline.
 */
function artpal_pricing_format_percent( $old, $new ) {
	$old = (float) $old;
	if ( $old <= 0 ) {
		return '';
	}
	$pct = ( (float) $new - $old ) / $old * 100;
	return number_format( $pct, 1, '.', '' ) . '%';
}

/**
 * @param string $field price or shipping.
 * @param int    $count How many changed.
 * @return string
 */
function artpal_pricing_changed_noun( $field, $count ) {
	$one = ( 1 === (int) $count );
	if ( 'shipping' === $field ) {
		return $one ? 'shipping price' : 'shipping prices';
	}
	return $one ? 'price' : 'prices';
}

/**
 * @param array $row Plan row.
 * @return string
 */
function artpal_pricing_status_text( $row ) {
	$text   = isset( $row['status_label'] ) ? (string) $row['status_label'] : '';
	$status = isset( $row['post_status'] ) ? (string) $row['post_status'] : 'publish';
	$extra  = array(
		'draft'   => 'draft',
		'pending' => 'pending review',
		'private' => 'private',
		'future'  => 'scheduled',
	);
	if ( isset( $extra[ $status ] ) ) {
		$text .= ', ' . $extra[ $status ];
	}
	return $text;
}

/**
 * @param array $rows         Six request rows.
 * @param string $field       price or shipping.
 * @param bool   $include_free Include free shipping.
 * @param string $fingerprint Plan fingerprint.
 * @return void
 */
function artpal_pricing_render_hidden_rules( $rows, $field, $include_free, $fingerprint ) {
	echo '<input type="hidden" name="artpal_field" value="' . esc_attr( $field ) . '" />';
	echo '<input type="hidden" name="artpal_include_free" value="' . ( $include_free ? '1' : '0' ) . '" />';
	echo '<input type="hidden" name="artpal_fingerprint" value="' . esc_attr( $fingerprint ) . '" />';
	foreach ( $rows as $i => $row ) {
		foreach ( array( 'min', 'max', 'amount', 'mode', 'round' ) as $key ) {
			$value = isset( $row[ $key ] ) ? $row[ $key ] : '';
			echo '<input type="hidden" name="artpal_rules[' . (int) $i . '][' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" />';
		}
	}
}

/**
 * Preview table, or the same table after a successful apply.
 *
 * @param array  $plan    Plan.
 * @param bool   $applied True when the plan was just saved.
 * @param array  $rows    Request rows, so Apply can post them again.
 * @param bool   $include_free Include-free flag to post again.
 * @return void
 */
function artpal_pricing_render_plan( $plan, $applied, $rows, $include_free ) {
	$field   = $plan['field'];
	$changed = (int) $plan['changed'];
	$noun    = artpal_pricing_changed_noun( $field, $changed );
	$before  = (float) $plan['total_before'];
	$after   = (float) $plan['total_after'];
	$heading = 'Preview';
	if ( $applied ) {
		$heading = ( 'shipping' === $field ) ? 'Shipping prices updated' : 'Prices updated';
	}

	echo '<h2>' . esc_html( $heading ) . '</h2>';

	if ( $changed === 0 ) {
		echo '<p>Nothing would change.</p>';
	} elseif ( $applied ) {
		echo '<p>Updated <strong>' . (int) $changed . '</strong> ' . esc_html( $noun ) . '.</p>';
	} else {
		echo '<p><strong>' . (int) $changed . '</strong> ' . esc_html( $noun ) . ' would change. Nothing is saved until you apply.</p>';
	}

	echo '<ul>';
	if ( $changed > 0 ) {
		echo '<li>Total before: ' . esc_html( artpal_pricing_format_money( $before ) ) . '</li>';
		echo '<li>Total after: ' . esc_html( artpal_pricing_format_money( $after ) ) . '</li>';
		echo '<li>Difference: ' . esc_html( artpal_pricing_format_diff( $after - $before ) ) . '</li>';
		$average = artpal_pricing_format_percent( $before, $after );
		if ( $average !== '' ) {
			echo '<li>Average increase: ' . esc_html( $average ) . '</li>';
		}
		echo '<li>These totals are only the items that change.</li>';
	}
	if ( (int) $plan['unchanged'] > 0 ) {
		echo '<li>' . (int) $plan['unchanged'] . ' left as they are, because they sit outside these ranges or are already rounded.</li>';
	}
	if ( 'price' === $field && (int) $plan['no_value'] > 0 ) {
		echo '<li>' . (int) $plan['no_value'] . ' unsold items have no price, so they were skipped.</li>';
	}
	if ( 'shipping' === $field && (int) $plan['skipped_free'] > 0 ) {
		echo '<li>' . (int) $plan['skipped_free'] . ' with free shipping were left free.</li>';
	}
	if ( (int) $plan['no_value'] > 0 && 'shipping' === $field ) {
		echo '<li>' . (int) $plan['no_value'] . ' had a shipping value that is not a number, so they were skipped.</li>';
	}
	echo '</ul>';

	if ( $changed === 0 ) {
		return;
	}

	echo '<table class="widefat striped" style="max-width:960px">';
	echo '<thead><tr>';
	echo '<th scope="col">Item</th><th scope="col">Status</th><th scope="col">Old</th><th scope="col">New</th><th scope="col">Difference</th><th scope="col">%</th>';
	echo '</tr></thead><tbody>';
	foreach ( $plan['rows'] as $row ) {
		$link  = get_edit_post_link( (int) $row['post_id'], 'raw' );
		$title = (string) $row['title'];
		echo '<tr>';
		echo '<th scope="row">';
		if ( is_string( $link ) && $link !== '' ) {
			echo '<a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a>';
		} else {
			echo esc_html( $title );
		}
		echo ' <span style="color:#646970">#' . (int) $row['post_id'] . '</span>';
		echo '</th>';
		echo '<td>' . esc_html( artpal_pricing_status_text( $row ) ) . '</td>';
		echo '<td>' . esc_html( artpal_pricing_format_money( $row['old'] ) ) . '</td>';
		echo '<td>' . esc_html( artpal_pricing_format_money( $row['new'] ) ) . '</td>';
		echo '<td>' . esc_html( artpal_pricing_format_diff( $row['diff'] ) ) . '</td>';
		echo '<td>' . esc_html( artpal_pricing_format_percent( $row['old'], $row['new'] ) ) . '</td>';
		echo '</tr>';
	}
	echo '</tbody><tfoot><tr>';
	echo '<th scope="row" colspan="2">Total</th>';
	echo '<td>' . esc_html( artpal_pricing_format_money( $before ) ) . '</td>';
	echo '<td>' . esc_html( artpal_pricing_format_money( $after ) ) . '</td>';
	echo '<td>' . esc_html( artpal_pricing_format_diff( $after - $before ) ) . '</td>';
	echo '<td>' . esc_html( artpal_pricing_format_percent( $before, $after ) ) . '</td>';
	echo '</tr></tfoot></table>';

	if ( $applied ) {
		return;
	}

	$apply_label = ( 'shipping' === $field ) ? 'Apply these shipping prices' : 'Apply these prices';
	echo '<form method="post" action="" style="margin-top:12px">';
	wp_nonce_field( 'artpal_prices_apply' );
	artpal_pricing_render_hidden_rules( $rows, $field, $include_free, $plan['fingerprint'] );
	echo '<input type="submit" name="artpal_prices_apply" class="button button-primary" value="' . esc_attr( $apply_label ) . '" />';
	echo '</form>';
}

/**
 * @return void
 */
function artpal_pricing_render_last_run() {
	$run = get_option( ARTPAL_LAST_PRICE_RUN, array() );
	if ( ! is_array( $run ) || empty( $run['rows'] ) || ! is_array( $run['rows'] ) ) {
		return;
	}

	$field = ( isset( $run['field'] ) && $run['field'] === 'shipping' ) ? 'shipping' : 'price';
	$count = count( $run['rows'] );
	$when  = isset( $run['at'] ) ? (string) $run['at'] : '';
	$stamp = strtotime( $when );
	if ( $stamp ) {
		$when = gmdate( 'M j, Y g:i a', $stamp ) . ' UTC';
	}

	echo '<h2>Last run</h2>';
	echo '<form method="post" action="">';
	wp_nonce_field( 'artpal_prices_undo' );
	echo '<p>' . esc_html( ucfirst( artpal_pricing_changed_noun( $field, $count ) ) ) . ' increased';
	if ( $when !== '' ) {
		echo ' on ' . esc_html( $when );
	}
	echo '. ' . (int) $count . ' ' . esc_html( ( 1 === $count ) ? 'item' : 'items' ) . '.';
	if ( isset( $run['totals']['total_before'], $run['totals']['total_after'] ) ) {
		echo ' Total went from ' . esc_html( artpal_pricing_format_money( $run['totals']['total_before'] ) );
		echo ' to ' . esc_html( artpal_pricing_format_money( $run['totals']['total_after'] ) ) . '.';
	}
	echo '</p>';
	echo '<p><input type="submit" name="artpal_prices_undo" class="button" value="Undo last run" onclick="return confirm(\'Restore the last increase? Items edited since then stay as they are.\');" /></p>';
	echo '</form>';
}

/**
 * Tools → ArtPal Prices.
 *
 * @return void
 */
function artpal_render_prices_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have permission to manage ArtPal prices.' );
	}

	$notice       = '';
	$notice_class = 'updated';
	$errors       = array();
	$plan         = null;
	$applied      = false;
	$field        = 'price';
	$include_free = false;
	$rows         = artpal_pricing_request_rows( array() );

	if ( isset( $_POST['artpal_prices_undo'] ) ) {
		check_admin_referer( 'artpal_prices_undo' );
		$result = artpal_pricing_undo_last_run();
		if ( ! empty( $result['missing'] ) ) {
			$notice       = 'There is no price increase to undo.';
			$notice_class = 'error';
		} else {
			$notice = 'Restored ' . (int) $result['restored'] . ' ' . ( 1 === (int) $result['restored'] ? 'item' : 'items' ) . '.';
			if ( (int) $result['skipped'] > 0 ) {
				$notice .= ' Left ' . (int) $result['skipped'] . ' that were edited after the increase.';
			}
		}
	} elseif ( isset( $_POST['artpal_prices_apply'] ) || isset( $_POST['artpal_prices_preview'] ) ) {
		$action = isset( $_POST['artpal_prices_apply'] ) ? 'artpal_prices_apply' : 'artpal_prices_preview';
		check_admin_referer( $action );

		$field        = ( isset( $_POST['artpal_field'] ) && wp_unslash( $_POST['artpal_field'] ) === 'shipping' ) ? 'shipping' : 'price';
		$include_free = isset( $_POST['artpal_include_free'] ) && (string) wp_unslash( $_POST['artpal_include_free'] ) === '1';
		$posted_rules = isset( $_POST['artpal_rules'] ) ? wp_unslash( $_POST['artpal_rules'] ) : array();
		$rows         = artpal_pricing_request_rows( $posted_rules );
		$parsed       = artpal_pricing_parse_rules( $rows );

		if ( ! empty( $parsed['errors'] ) ) {
			$errors = $parsed['errors'];
		} elseif ( isset( $_POST['artpal_prices_apply'] ) ) {
			$fingerprint = isset( $_POST['artpal_fingerprint'] ) ? (string) wp_unslash( $_POST['artpal_fingerprint'] ) : '';
			$result      = artpal_pricing_apply( $field, $parsed['rules'], $include_free, $fingerprint );
			$plan        = $result['plan'];
			if ( empty( $result['ok'] ) ) {
				$notice       = 'Some prices changed after the preview, so nothing was saved. The list below is current. Apply it if it still looks right.';
				$notice_class = 'error';
			} elseif ( empty( $result['record'] ) ) {
				$notice = 'Nothing needed a new price.';
			} else {
				$applied = true;
				$notice  = ( 'shipping' === $field ) ? 'Shipping prices updated.' : 'Prices updated.';
			}
		} else {
			$plan = artpal_pricing_build_plan( $field, $parsed['rules'], $include_free );
		}
	}

	$symbol = html_entity_decode( (string) get_option( 'ds_ap_currencysymbol' ), ENT_QUOTES, 'UTF-8' );
	if ( $symbol === '' ) {
		$symbol = '$';
	}

	echo '<div class="wrap">';
	echo '<h1>ArtPal Prices</h1>';
	echo '<p>Raise the price or the shipping on unsold work in one pass. Sold work is left alone. Preview the list first. Nothing is saved until you apply it.</p>';
	echo '<p>This includes drafts, private posts, and posts marked not currently available, as long as they are in Available and not Sold.</p>';

	if ( $notice !== '' ) {
		echo '<div id="message" class="' . esc_attr( $notice_class ) . ' notice is-dismissible"><p><strong>' . esc_html( $notice ) . '</strong></p></div>';
	}
	if ( ! empty( $errors ) ) {
		echo '<div class="error notice"><p><strong>Fix these before previewing.</strong></p><ul>';
		foreach ( $errors as $error ) {
			echo '<li>' . esc_html( $error ) . '</li>';
		}
		echo '</ul></div>';
	}

	if ( is_array( $plan ) ) {
		artpal_pricing_render_plan( $plan, $applied, $rows, $include_free );
	}

	artpal_pricing_render_last_run();

	echo '<h2>Price ranges</h2>';
	echo '<form method="post" action="" autocomplete="off">';
	wp_nonce_field( 'artpal_prices_preview' );

	echo '<fieldset><legend class="screen-reader-text">Which amount to raise</legend>';
	echo '<p><label><input type="radio" name="artpal_field" value="price"' . checked( $field, 'price', false ) . ' /> Price</label><br />';
	echo '<label><input type="radio" name="artpal_field" value="shipping"' . checked( $field, 'shipping', false ) . ' /> Shipping</label></p>';
	echo '</fieldset>';

	echo '<p><label><input type="checkbox" name="artpal_include_free" value="1"' . checked( $include_free, true, false ) . ' /> Also raise free shipping. Leave this unchecked and free shipping stays free. This only applies to Shipping.</label></p>';

	echo '<table class="widefat" style="max-width:820px"><thead><tr>';
	echo '<th scope="col">From (' . esc_html( $symbol ) . ')</th>';
	echo '<th scope="col">Up to (' . esc_html( $symbol ) . ')</th>';
	echo '<th scope="col">Increase</th>';
	echo '<th scope="col">Round up to (' . esc_html( $symbol ) . ')</th>';
	echo '</tr></thead><tbody>';

	foreach ( $rows as $i => $row ) {
		$n = $i + 1;
		echo '<tr>';
		echo '<td><input type="number" step="any" min="0" class="small-text" name="artpal_rules[' . (int) $i . '][min]" value="' . esc_attr( $row['min'] ) . '" aria-label="Row ' . (int) $n . ' from" /></td>';
		echo '<td><input type="number" step="any" min="0" class="small-text" name="artpal_rules[' . (int) $i . '][max]" value="' . esc_attr( $row['max'] ) . '" aria-label="Row ' . (int) $n . ' up to" /></td>';
		echo '<td><input type="number" step="any" min="0" class="small-text" name="artpal_rules[' . (int) $i . '][amount]" value="' . esc_attr( $row['amount'] ) . '" aria-label="Row ' . (int) $n . ' increase" /> ';
		echo '<select name="artpal_rules[' . (int) $i . '][mode]" aria-label="Row ' . (int) $n . ' increase type">';
		echo '<option value="percent"' . selected( $row['mode'], 'percent', false ) . '>%</option>';
		echo '<option value="flat"' . selected( $row['mode'], 'flat', false ) . '>' . esc_html( $symbol ) . '</option>';
		echo '</select></td>';
		echo '<td><input type="number" step="any" min="0" class="small-text" name="artpal_rules[' . (int) $i . '][round]" value="' . esc_attr( $row['round'] ) . '" aria-label="Row ' . (int) $n . ' round up to" /></td>';
		echo '</tr>';
	}
	echo '</tbody></table>';

	echo '<p class="description">From is included. Up to is not, so a painting at exactly that price uses the next row. Leave Up to empty for no upper limit. The increase is applied first, then the price is rounded up. Leave Round up empty to keep cents. Empty rows are ignored.</p>';
	echo '<p class="description">For example: $0 up to $100, increase 30%, round up to $5 turns $10 into $15. $100 up to $1,000, increase 20%, round up to $5 turns $95 into $115. $1,000 up to $2,000, increase 20%, round up to $100 stays $1,200. $2,000 and up, add $100, round up to $250 turns $2,450 into $2,750.</p>';
	echo '<p><input type="submit" name="artpal_prices_preview" class="button button-primary" value="Preview" /></p>';
	echo '</form>';
	echo '</div>';
}
