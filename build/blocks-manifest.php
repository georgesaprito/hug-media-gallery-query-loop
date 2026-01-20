<?php
// This file is generated. Do not modify it manually.
return array(
	'hug-media-gallery-query-loop' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'create-block/hug-media-gallery-query-loop',
		'version' => '0.1.0',
		'title' => 'Hug Media Gallery Query Loop',
		'category' => 'widgets',
		'icon' => 'smiley',
		'description' => 'Example block scaffolded with Create Block tool. Requires Media Library Categories plugin.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'align' => true,
			'alignWide' => true
		),
		'textdomain' => 'hug-media-gallery-query-loop',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'attributes' => array(
			'mediaTaxonomy' => array(
				'type' => 'string',
				'default' => ''
			),
			'sortOption' => array(
				'type' => 'string',
				'default' => 'newest_to_oldest'
			),
			'imageSize' => array(
				'type' => 'string',
				'default' => 'medium'
			),
			'perPage' => array(
				'type' => 'number',
				'default' => 12
			),
			'columns' => array(
				'type' => 'number',
				'default' => 3
			),
			'useLightbox' => array(
				'type' => 'boolean',
				'default' => false
			),
			'layoutStyle' => array(
				'type' => 'string',
				'default' => 'grid'
			),
			'showTitles' => array(
				'type' => 'boolean',
				'default' => false
			)
		)
	)
);
