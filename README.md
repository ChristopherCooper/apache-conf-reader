# Apache Conf Reader

## Add to your composer file

I'll add this to Packagist later, in the mean time add these to your composer file:

```json
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:ChristopherCooper/apache-conf-reader.git"
    }
  ],
  "require": {
    "chriscooper/apache-conf-reader": "dev-master",
  },
```

## Example

```php
  $conf = (new ApacheConfig('stubs/simple.conf'))->handle();

  /** @var \ChrisCooper\ApacheConfReader\Nodes\VirtualHost $virtual_host */
  foreach ($conf['VirtualHosts'] as $virtual_host) {
    print_r($virtual_host);
  }

  /** @var \ChrisCooper\ApacheConfReader\Nodes\Directory $directory */
  foreach ($conf['Directories'] as $directory) {
    print_r($directory);
  }
```

Output:

```
ChrisCooper\ApacheConfReader\Nodes\VirtualHost Object (
  [address] => 127.0.0.1:8008
  [params] => Array (
    [ServerName] => Array (
      [0] => dev.visualsoft.co.uk
    )
    [DocumentRoot] => Array (
      [0] => /export/default_site/html
    )
    [Include] => Array (
      [0] => /etc/apache/conf.d/trace.conf
    )
  )
)
ChrisCooper\ApacheConfReader\Nodes\Directory Object (
  [location] => /export/default_site/html/
  [params] => Array (
    [AddType] => Array (
      [0] => text/plain .php .phtml .php3 .phps .html .htm .asp .js .fdf .php3 .css
    )
  )
)
```