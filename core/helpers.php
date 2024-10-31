<?php

if ( ! function_exists( 'excpf_kses_criteria' ) ) {
	function excpf_kses_criteria() {
		add_filter(
			'safe_style_css',
			function( $styles ) {
				$styles = array_merge( $styles, array( 'display' ) );
				return $styles;
			}
		);
		$allowed_atts = array(
			'align'        => array(),
			'class'        => array(),
			'type'         => array(),
			'id'           => array(),
			'dir'          => array(),
			'lang'         => array(),
			'style'        => array(),
			'xml:lang'     => array(),
			'src'          => array(),
			'alt'          => array(),
			'href'         => array(),
			'rel'          => array(),
			'rev'          => array(),
			'target'       => array(),
			'novalidate'   => array(),
			'type'         => array(),
			'value'        => array(),
			'name'         => array(),
			'tabindex'     => array(),
			'action'       => array(),
			'method'       => array(),
			'for'          => array(),
			'width'        => array(),
			'height'       => array(),
			'data'         => array(),
			'title'        => array(),
			'onclick'      => array(),
			'autocomplete' => array(),
			'readonly'     => array(),
			'placeholder'  => array(),
			'colspan'      => array(),
			'valign'       => array(),
			'onkeyup'      => array(),
			'onchange'     => array(),
			'data-id'      => array(),
			'selected'     => array(),
			'checked'      => array(),
			'service_name' => array(),
			'mapto'        => array(),
			'style'        => array(),
			'disabled'     => array(),
		);

		$allowedposttags['form']     = $allowed_atts;
		$allowedposttags['label']    = $allowed_atts;
		$allowedposttags['input']    = $allowed_atts;
		$allowedposttags['textarea'] = $allowed_atts;
		$allowedposttags['iframe']   = $allowed_atts;
		$allowedposttags['script']   = $allowed_atts;
		$allowedposttags['style']    = $allowed_atts;
		$allowedposttags['strong']   = $allowed_atts;
		$allowedposttags['small']    = $allowed_atts;
		$allowedposttags['table']    = $allowed_atts;
		$allowedposttags['thead']    = $allowed_atts;
		$allowedposttags['tbody']    = $allowed_atts;
		$allowedposttags['th']       = $allowed_atts;
		$allowedposttags['tr']       = $allowed_atts;
		$allowedposttags['td']       = $allowed_atts;
		$allowedposttags['span']     = $allowed_atts;
		$allowedposttags['abbr']     = $allowed_atts;
		$allowedposttags['code']     = $allowed_atts;
		$allowedposttags['pre']      = $allowed_atts;
		$allowedposttags['div']      = $allowed_atts;
		$allowedposttags['img']      = $allowed_atts;
		$allowedposttags['h1']       = $allowed_atts;
		$allowedposttags['h2']       = $allowed_atts;
		$allowedposttags['h3']       = $allowed_atts;
		$allowedposttags['h4']       = $allowed_atts;
		$allowedposttags['h5']       = $allowed_atts;
		$allowedposttags['h6']       = $allowed_atts;
		$allowedposttags['ol']       = $allowed_atts;
		$allowedposttags['ul']       = $allowed_atts;
		$allowedposttags['li']       = $allowed_atts;
		$allowedposttags['em']       = $allowed_atts;
		$allowedposttags['hr']       = $allowed_atts;
		$allowedposttags['br']       = $allowed_atts;
		$allowedposttags['tr']       = $allowed_atts;
		$allowedposttags['td']       = $allowed_atts;
		$allowedposttags['p']        = $allowed_atts;
		$allowedposttags['a']        = $allowed_atts;
		$allowedposttags['b']        = $allowed_atts;
		$allowedposttags['i']        = $allowed_atts;
		$allowedposttags['select']   = $allowed_atts;
		$allowedposttags['option']   = $allowed_atts;
		$allowedposttags['button']   = $allowed_atts;
		return $allowedposttags;
	}
}
