<?php
/**
 * WP FAQ Manager - Shortcodes Module
 *
 * Contains our shortcodes and related functionality.
 *
 * @package WordPress FAQ Manager
 */

/**
 * Start our engines.
 */
class WPFAQ_Manager_Shortcodes {

	/**
	 * Call our hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_shortcode( 'faq',                           array( $this, 'shortcode_main'          )           );
		add_shortcode( 'faqlist',                       array( $this, 'shortcode_list'          )           );
		add_shortcode( 'faqtaxlist',                    array( $this, 'shortcode_tax_list'      )           );
		add_shortcode( 'faqcombo',                      array( $this, 'shortcode_combo'         )           );
	}

	/**
	 * Our primary shortcode display.
	 *
	 * @param  array $atts     The shortcode attributes.
	 * @param  mixed $content  The content on the post being displayed.
	 *
	 * @return mixed           The original content with our shortcode data.
	 */
	public function shortcode_main( $atts, $content = null ) {

		// Parse my attributes.
		$atts   = shortcode_atts( array(
			'faq_topic' => '',
			'faq_tag'   => '',
			'faq_id'    => 0,
			'limit'     => 10,
		), $atts, 'faq' );

		// Set each possible taxonomy into an array.
		$topics = ! empty( $atts['faq_topic'] ) ? explode( ',', esc_attr( $atts['faq_topic'] ) ) : array();
		$tags   = ! empty( $atts['faq_tag'] ) ? explode( ', ', esc_attr( $atts['faq_tag'] ) ) : array();

		// Determine my pagination set.
		$paged  = ! empty( $_GET['faq_page'] ) ? absint( $_GET['faq_page'] ) : 1;

		// Fetch my items.
		if ( false === $faqs = WPFAQ_Manager_Data::get_main_shortcode_faqs( $atts['faq_id'], $atts['limit'], $topics, $tags, $paged ) ) {
			return;
		}

		// Call our CSS file.
		wp_enqueue_style( 'faq-front' );

		// Set some variables used within.
		$speed  = apply_filters( 'wpfaq_display_expand_speed', 200, 'main' );
		$filter = apply_filters( 'wpfaq_display_content_filter', true, 'main' );
		$expand = apply_filters( 'wpfaq_display_content_expand', true, 'main' );
		$htype  = apply_filters( 'wpfaq_display_htype', 'h3', 'main' );
		$exlink = apply_filters( 'wpfaq_display_content_more_link', array( 'show' => 1, 'text' => 'Read More' ), 'main' );
		$pageit = apply_filters( 'wpfaq_display_shortcode_paginate', true, 'main' );

		// Make sure we have a valid H type to use.
		$htype  = WPFAQ_Manager_Helper::check_htype_tag( $htype );

		// Set some classes for markup.
		$bclass = ! empty( $expand ) ? 'single-faq expand-faq' : 'single-faq';
		$tclass = ! empty( $expand ) ? 'faq-question expand-title' : 'faq-question';

		// Start my markup.
		$build  = '';

		// The wrapper around.
		$build .= '<div id="faq-block" name="faq-block">';
			$build .= '<div class="faq-list" data-speed="' . absint( $speed ) . '">';

			// Loop my individual FAQs
			foreach ( $faqs as $faq ) {

				// Wrap a div around each item.
				$build .= '<div class="' . esc_attr( $bclass ) . '">';

					// Our title setup.
					$build .= '<' . esc_attr( $htype ) . ' id="' . esc_attr( $faq->post_name ) . '" name="' . esc_attr( $faq->post_name ) . '" class="' . esc_attr( $tclass ) . '">' . esc_html( $faq->post_title ) .  '</' . esc_attr( $htype ) . '>';

					// Our content display.
					$build .= '<div class="faq-answer" rel="' . esc_attr( $faq->post_name ) . '">';

					// Show the content, with the optional filter.
					$build .= false !== $filter ? apply_filters( 'the_content', $faq->post_content ) : $faq->post_content;

					// Show the "read more" link.
					if ( ! empty( $exlink ) ) {

						// Fetch the link and text to display.
						$link   = get_permalink( absint( $faq->ID ) );
						$more   = ! empty( $exlink['text'] ) ? $exlink['text'] : 'Read More';

						// The display portion itself.
						$build .= '<p class="faq-link">';
						$build .= '<a href="' . esc_url( $link ) . '" title="' . esc_attr( $faq->post_title ) .  '">' . esc_html( $more ) . '</a>';
						$build .= '</p>';
					}

					// Close the div around the content display.
					$build .= '</div>';

				// Close the div around each item.
				$build .= '</div>';
			}

			// Handle our optional pagination.
			if ( ! empty( $pageit ) && empty( $atts['faq_id'] ) ) {

				// Get the base link setup for pagination.
				$base   = trailingslashit( get_permalink() );

				// Figure out our total.
				$total  = WPFAQ_Manager_Data::get_total_faq_count( $atts['limit'] );

				// The actual pagination args.
				$pargs  = array(
					'base'      => $base . '%_%',
					'format'    => '?faq_page=%#%',
					'type'      => 'plain',
					'current'   => $paged,
					'total'     => $total,
					'prev_text' => __( '&laquo;' ),
					'next_text' => __( '&raquo;' ),
				);

				// The wrapper for pagination.
				$build .= '<p class="faq-nav">';

				// The actual pagination call with our filtered args.
				$build .= paginate_links( apply_filters( 'wpfaq_shortcode_paginate_args', $pargs, 'main' ) );

				// The closing markup for pagination.
				$build .= '</p>';
			}

			// Close the markup wrappers.
			$build .= '</div>';
		$build .= '</div>';

		// Return my markup.
		return $build;
	}

	/**
	 * Our list version of the shortcode display.
	 *
	 * @param  array $atts     The shortcode attributes.
	 * @param  mixed $content  The content on the post being displayed.
	 *
	 * @return mixed           The original content with our shortcode data.
	 */
	public function shortcode_list( $atts, $content = null ) {

		// Parse my attributes.
		$atts   = shortcode_atts( array(
			'faq_topic' => '',
			'faq_tag'   => '',
			'faq_id'    => 0,
			'limit'     => 10,
		), $atts, 'faqlist' );

		// Set each possible taxonomy into an array.
		$topics = ! empty( $atts['faq_topic'] ) ? explode( ',', esc_attr( $atts['faq_topic'] ) ) : array();
		$tags   = ! empty( $atts['faq_tag'] ) ? explode( ',', esc_attr( $atts['faq_tag'] ) ) : array();

		// Determine my pagination set.
		$paged  = ! empty( $_GET['faq_page'] ) ? absint( $_GET['faq_page'] ) : 1;

		// Fetch my items.
		if ( false === $faqs = WPFAQ_Manager_Data::get_main_shortcode_faqs( $atts['faq_id'], $atts['limit'], $topics, $tags, $paged ) ) {
			return;
		}

		// Call our CSS file.
		wp_enqueue_style( 'faq-front' );

		// Set some variables used within.
		$pageit = apply_filters( 'wpfaq_display_shortcode_paginate', true, 'list' );

		// Start my markup.
		$build  = '';

		// The wrapper around.
		$build .= '<div id="faq-block" name="faq-block">';
			$build .= '<div class="faq-list">';

			// Set up a list wrapper.
			$build .= '<ul>';

			// Loop my individual FAQs
			foreach ( $faqs as $faq ) {

				// Get my permalink.
				$link   = get_permalink( $faq->ID );

				// Wrap a li around each item.
				$build .= '<li class="faqlist-question">';

				// The actual link.
				$build .= '<a href="' . esc_url( $link ) . '" title="' . esc_attr( $faq->post_title ) .  '">' . esc_html( $faq->post_title ) .  '</a>';

				// Close the li around each item.
				$build .= '</li>';
			}

			// Close up the list wrapper.
			$build .= '</ul>';

			// Handle our optional pagination.
			if ( ! empty( $pageit ) && empty( $atts['faq_id'] ) ) {

				// Get the base link setup for pagination.
				$base   = trailingslashit( get_permalink() );

				// Figure out our total.
				$total  = WPFAQ_Manager_Data::get_total_faq_count( $atts['limit'] );

				// The actual pagination args.
				$pargs  = array(
					'base'      => $base . '%_%',
					'format'    => '?faq_page=%#%',
					'type'      => 'plain',
					'current'   => $paged,
					'total'     => $total,
					'prev_text' => __( '&laquo;' ),
					'next_text' => __( '&raquo;' ),
				);

				// The wrapper for pagination.
				$build .= '<p class="faq-nav">';

				// The actual pagination call with our filtered args.
				$build .= paginate_links( apply_filters( 'wpfaq_shortcode_paginate_args', $pargs, 'list' ) );

				// The closing markup for pagination.
				$build .= '</p>';
			}

			// Close the markup wrappers.
			$build .= '</div>';
		$build .= '</div>';

		// Return my markup.
		return $build;
	}

	/**
	 * Our list of taxonomies of the shortcode display.
	 *
	 * @param  array $atts     The shortcode attributes.
	 * @param  mixed $content  The content on the post being displayed.
	 *
	 * @return mixed           The original content with our shortcode data.
	 */
	public function shortcode_tax_list( $atts, $content = null ) {

		// Parse my attributes.
		$atts   = shortcode_atts( array(
			'type'      => 'topics',
			'desc'      => '',
			'linked'    => true,
		), $atts, 'faqtaxlist' );

		// If no type is set, or it's not a valid one, bail.
		if ( empty( $atts['type'] ) || ! in_array( esc_attr( $atts['type'] ), array( 'topics', 'tags' ) ) ) {
			return;
		}

		// Now set the actual type we have registered, along with the description flag.
		$type   = ! empty( $atts['type'] ) && 'topics' === esc_attr( $atts['type'] ) ? 'faq-topic' : 'faq-tag';

		// Fetch my terms.
		if ( false === $terms = WPFAQ_Manager_Data::get_tax_shortcode_terms( $type ) ) {
			return;
		}

		// Call our CSS file.
		wp_enqueue_style( 'faq-front' );

		// Some display variables.
		$htype  = apply_filters( 'wpfaq_display_htype', 'h3', 'taxlist' );

		// Make sure we have a valid H type to use.
		$htype  = WPFAQ_Manager_Helper::check_htype_tag( $htype );

		// Start my markup.
		$build  = '';

		// The wrapper around.
		$build .= '<div id="faq-block" name="faq-block" class="faq-taxonomy faq-taxonomy-' . sanitize_html_class( $type ) . '">';

		// Loop my individual terms
		foreach ( $terms as $term ) {

			// Wrap a div around each item.
			$build .= '<div id="' . esc_attr( $term->slug ) . '" class="faq-item faq-taxlist-item">';

				// Our title setup.
				$build .= '<' . esc_attr( $htype ) . ' name="' . esc_attr( $term->slug ) . '">';

				// The title name (linked or otherwise).
				$build .= ! empty( $atts['linked'] ) ? '<a href="' . get_term_link( $term, $type ) . '">' . esc_html( $term->name ) . '</a>' : esc_html( $term->name );

				// Close the title.
				$build .= '</' . esc_attr( $htype ) . '>';

				// Optional description.
				if ( ! empty( $atts['desc'] ) && ! empty( $term->description ) ) {
					$build .= wpautop( esc_attr( $term->description ) );
				}

			// Close the div around each item.
			$build .= '</div>';
		}

		// Close the wrapper
		$build .= '</div>';

		// Return my markup.
		return $build;
	}

	/**
	 * Our list of taxonomies of the shortcode display.
	 *
	 * @param  array $atts     The shortcode attributes.
	 * @param  mixed $content  The content on the post being displayed.
	 *
	 * @return mixed           The original content with our shortcode data.
	 */
	public function shortcode_combo( $atts, $content = null ) {

		// Parse my attributes.
		$atts   = shortcode_atts( array(
			'faq_topic' => '',
			'faq_tag'   => '',
			'faq_id'    => 0,
		), $atts, 'faqcombo' );

		// Set each possible taxonomy into an array.
		$topics = ! empty( $atts['faq_topic'] ) ? explode( ',', esc_attr( $atts['faq_topic'] ) ) : array();
		$tags   = ! empty( $atts['faq_tag'] ) ? explode( ',', esc_attr( $atts['faq_tag'] ) ) : array();

		// Fetch my items.
		if ( false === $faqs = WPFAQ_Manager_Data::get_combo_shortcode_faqs( $atts['faq_id'], $topics, $tags ) ) {
			return;
		}

		// Call our CSS file.
		wp_enqueue_style( 'faq-front' );

		// Some display variables.
		$filter = apply_filters( 'wpfaq_display_content_filter', true, 'combo' );
		$htype  = apply_filters( 'wpfaq_display_htype', 'h3', 'combo' );

		// Make sure we have a valid H type to use.
		$htype  = in_array( $htype, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', ) ) ? $htype : 'h3';

		// Start my markup.
		$build  = '';

		// The wrapper around the entire thing.
		$build .= '<div id="faq-block" name="faq-block" rel="faq-top">';

			// Wrap the list portion of the combo.
			$build .= '<div class="faq-list">';
				$build .= '<ul>';

				// Loop my individual FAQs
				foreach ( $faqs as $faq ) {

					// Wrap a li around each item.
					$build .= '<li class="faqlist-question">';

					// The actual link.
					$build .= '<a href="#' . esc_attr( $faq->post_name ) . '" rel="' . esc_attr( $faq->post_name ) . '">' . esc_html( $faq->post_title ) .  '</a>';

					// Close the li around each item.
					$build .= '</li>';
				}

				// Close the wrapper around the list portion.
				$build .= '</ul>';
			$build .= '</div>';

			// Wrap the content portion of the combo.
			$build .= '<div class="faq-content">';

				// Loop my individual FAQs
				foreach ( $faqs as $faq ) {

					// Wrap a div around each item.
					$build .= '<div class="single-faq" rel="' . esc_attr( $faq->post_name ) . '">';

						// Our title setup.
						$build .= '<' . esc_attr( $htype ) . ' id="' . esc_attr( $faq->post_name ) . '" name="' . esc_attr( $faq->post_name ) . '" class="faq-question">' . esc_html( $faq->post_title ) .  '</' . esc_attr( $htype ) . '>';

						// Handle the content itself.
						$build .= '<div class="faq-answer">';

							// Show the content, with the optional filter.
							$build .= false !== $filter ? apply_filters( 'the_content', $faq->post_content ) : $faq->post_content;

							// Show the "back to top" if requested.
							if ( false !== apply_filters( 'wpfaq_display_content_backtotop', true, 'combo' ) ) {
								$build .= '<p class="scroll-back"><a href="#faq-block">' . __( 'Back To Top', 'wordpress-faq-manager' ) . '</a></p>';
							}

						// Close the div around each bit of content.
						$build .= '</div>';

					// Close the div around each item.
					$build .= '</div>';
				}

			// Close the wrap the content portion of the combo.
			$build .= '</div>';

		// Close the entire wrapper.
		$build .= '</div>';

		// Return my markup.
		return $build;
	}

	// End our class.
}

// Call our class.
$WPFAQ_Manager_Shortcodes = new WPFAQ_Manager_Shortcodes();
$WPFAQ_Manager_Shortcodes->init();

