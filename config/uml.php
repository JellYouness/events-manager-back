<?php

// Entities should be in the correct order to avoid foreign key errors
return [
  'entities' => [
    [
      'model' => 'ColorsPalette',
      'table' => 'colors_palettes',
      'fields' => [
        'color1' => [
          'type' => 'string',
        ],
        'color2' => [
          'type' => 'string',
        ],
        'color3' => [
          'type' => 'string',
        ],
        'color4' => [
          'type' => 'string',
        ],
        'color5' => [
          'type' => 'string',
        ],
      ],
    ],
    [
      'model' => 'ShapesCategory',
      'table' => 'shapes_categories',
      'fields' => [
        'name' => [
          'type' => 'string',
          'unique' => true,
        ],
      ],
    ],
    [
      'model' => 'Shape',
      'table' => 'shapes',
      'fields' => [
        'shapes_category_id' => [
          'type' => 'foreign',
        ],
        'colors_palette_id' => [
          'type' => 'foreign',
        ],
        'image' => [
          'type' => 'string',
        ],
      ],
    ],
    [
      'model' => 'Brand',
      'table' => 'brands',
      'fields' => [
        'name' => [
          'type' => 'string',
          'unique' => true,
        ],
        'logo_image' => [
          'type' => 'string',
        ],
      ],
    ],
    [
      'model' => 'BrandModel',
      'table' => 'brand_models',
      'fields' => [
        'brand_id' => [
          'type' => 'foreign',
        ],
        'preview_image' => [
          'type' => 'string',
        ],
        'cylinder_capacity' => [
          'type' => 'integer',
        ],
        'year' => [
          'type' => 'integer',
        ],
      ],
    ],
    [
      'model' => 'ModelPart',
      'table' => 'model_parts',
      'fields' => [
        'brand_model_id' => [
          'type' => 'foreign',
        ],
        'right_side_view_image' => [
          'type' => 'string',
        ],
        'top_side_view_image' => [
          'type' => 'string',
          'nullable' => true,
        ],
        'symmetry_type' => [
          'type' => 'enum',
          'enum' => [
            'name' => 'SYMMETRY_TYPE',
            'values' => [
              'NONE',
              'ONE_FACE',
              'TWO_FACES',
            ]
          ]
        ],
        'symmetry_image' => [
          'type' => 'string',
          'nullable' => true,
        ],
        'canvajs_settings' => [
          'type' => 'json',
        ],
      ],
    ],
    [
      'model' => 'ModelDesign',
      'table' => 'model_designs',
      'fields' => [
        'model_part_id' => [
          'type' => 'foreign',
        ],
        'colors_palette_id' => [
          'type' => 'foreign',
        ],
        'preview_image' => [
          'type' => 'string',
        ],
      ],
    ],
    [
      'model' => 'ModelPartDesign',
      'table' => 'model_part_designs',
      'fields' => [
        'model_design_id' => [
          'type' => 'foreign',
        ],
        'shape_id' => [
          'type' => 'foreign',
        ],
        'colors_palette_id' => [
          'type' => 'foreign',
        ],
        'canvajs_settings' => [
          'type' => 'json',
        ],
      ],
    ],
    [
      'model' => 'LogosCategory',
      'table' => 'logos_categories',
      'fields' => [
        'name' => [
          'type' => 'string',
          'unique' => true,
        ],
      ],
    ],
    [
      'model' => 'Logo',
      'table' => 'logos',
      'fields' => [
        'logos_category_id' => [
          'type' => 'foreign',
        ],
        'colors_palette_id' => [
          'type' => 'foreign',
        ],
        'image' => [
          'type' => 'string',
        ],
      ],
    ],
    [
      'model' => 'Accessory',
      'table' => 'accessories',
      'fields' => [
        'colors_palette_id' => [
          'type' => 'foreign',
        ],
        'image' => [
          'type' => 'string',
        ],
      ],
    ],
  ]
];
