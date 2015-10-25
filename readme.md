Another Yii2 Sitemap module
===========================

Sitemap generator.

Main features:

  * Generates sitemap to file by command sitemap/write

  * Cares about memory (does not load much to memory)

  * Cares about filesize (by default split files by 10 000 record and 10MB; values could be tuned)

Installing
==========

Add following section to composer.json:

```
  // to "require" section:
  "idfly/yii2-sitemap": "dev-master"

  // to "repositories" section:
  {
      "type": "git",
      "url": "git@github.com:idfly/yii2-sitemap.git"
  },
```

Example configuration:

```
$config['modules']['sitemap'] = [
    'class' => 'idfly\sitemap\Module',

    'items' => [
        [
            'list' => function() {
                $query =
                    \app\models\Category::find()->
                    andWhere(['active' => 1])->
                    all();
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


Commiting
=========

This call will write 'web/sitemap_0.xml' and 'web/sitemap.xml'. Inspired by
(yii2-sitemap-module)[https://github.com/himiklab/yii2-sitemap-module] by
@himiklab. Package contains tests so don't forget to `phpunit tests` before
pushing or creating pull request. All new functionality should be tested before
pushing.


Usage without Yii2
==================

It is possible to use this functionality without yii2:

```
  $sitemap = new Sitemap('/path/to/public', '/url/to/site');

  $sitemap->items = [
      [
          'list' => function() {
              return $myorm->select();
          },
          'prepare' => function($object) {
              return [
                  'loc' => 'http://mydomain.com/' . $object->url,
              ];
          }
      ]
  ];

  $sitemap->write();
```