<?php
/**
 * Views functionality.
 *
 * @package wrd/wp-views
 */

namespace wrd\wp_views;

use WP_Post;

/**
 * Enable functionality for WordPress to find templates in the views directory.
 *
 * @return void
 */
function use_views_directory() {
	add_filter( 'template_include', __NAMESPACE__ . '\\_theme_use_views_directory', 10, 1 );
}

/**
 * Get the directory for storing view templates.
 *
 * @return string
 */
function get_views_directory(): string {
	$directory = '/views/';

	return apply_filters( 'wrd\wp_views\get_views_directory', $directory );
}

/**
 * Get templates from the views directory.
 *
 * @param string $template The current template to use.
 *
 * @return string
 *
 * @internal
 */
function _theme_use_views_directory( string $template ): string {
	$possible_templates = array();
	$post_type          = get_post_type();

	if ( is_archive() || ( is_home() && 'post' === $post_type ) ) {
		$possible_templates = array( 'archive.php' );
	}

	if ( is_singular() ) {
		$possible_templates = array( 'single.php' );
	}

	foreach ( $possible_templates as $template_name ) {
		$template_path = get_views_directory() . $post_type . '/' . $template_name;

		if ( file_exists( $template_path ) ) {
			return $template_path;
		}
	}

	return $template;
}

/**
 * Display a post preview.
 *
 * @param WP_Post|int|null $post The post to preview.
 *
 * @param ?string          $slug The preview slug. Optional.
 *
 * @param ?array           $args Arguments to pass to the template. Optional.
 *
 * @return string The template filename if one is located.
 */
function the_post_preview( WP_Post|int|null $post = null, ?string $slug = null, ?array $args = null ): string {
	$post      = get_post( $post );
	$post_type = get_post_type( $post );

	$default_templates = array(
		get_views_directory() . "/{$post_type}/preview-{$slug}.php",
		get_views_directory() . "/{$post_type}/preview.php",
		get_views_directory() . "/post/preview-{$slug}.php",
		get_views_directory() . '/post/preview.php',
		"preview-{$slug}.php",
		'preview.php',
	);

	$templates = apply_filters( 'wrd\wp_views\the_post_preview', $default_templates, $post, $slug, $args ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Namespaced.

	return locate_template( $templates, true, false, $args );
}

/**
 * Display previews for a group of posts.
 *
 * @param WP_Post[]|null $posts The posts. Defaults to global loop.
 *
 * @param ?string        $slug The preview slug. Optional.
 *
 * @param ?array         $args Arguments to pass to the template. Optional.
 *
 * @return void
 */
function the_post_preview_loop( ?array $posts = null, ?string $slug = null, ?array $args = null ): void {
	if ( null === $posts ) {
		while ( have_posts() ) {
			the_post();
			the_post_preview( get_post(), $slug, $args );
		}
	}

	if ( ! $posts ) {
		return;
	}

	global $post;

	foreach ( $posts as $post ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Get over it.
		setup_postdata( $post );
		the_post_preview( $post, $slug, $args );
	}

	wp_reset_postdata();
}
