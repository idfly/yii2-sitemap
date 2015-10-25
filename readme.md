Another Yii2 Sitemap module
===========================

Sitemap generator.

Main features:

  * Generates sitemap to file by command sitemap/write

  * Cares about memory (does not load much to memory)

  * Cares about filesize (by default split files by 10 000 record and 10MB; values could be tuned)

Example configuration:

```
$config['modules']['sitemap'] = [
    'class' => 'idfly\sitemap\Module',

    'items' => [
        [
            'list' => function() {
                $query = \app\models\Category::find();
                $query = \app\models\Category::addParentCategory($query);
                $query = $query->andWhere(['active' => 1]);
                return $query;
            },

            'prepare' => function($category) {
                $url = '/catalog/' . $category->url;

                if($category->level > 0) {
                    $url = '/catalog/' . $category->parent_url.
                        '?q[subcategories][]=' . $category->url;
                }

                return [
                    'loc' => $url,
                    'changefreq' => 'monthly',
                    'priority' => 0.5,
                ];
            }
        ],

        [
            'list' => [
                ['loc' => '/', 'changefreq' => 'daily', 'priority' => '0.1']
            ]
        ],
    ]
]
```

Example call:

```
  php yii sitemap/write
```

This call will write 'web/sitemap_0.xml' and 'web/sitemap.xml'. Inspired by
(yii2-sitemap-module)[https://github.com/himiklab/yii2-sitemap-module] by
@himiklab. Package contains tests so don't forget to `phpunit tests` before
pushing or creating pull request. All new functionality should be tested before
pushing.