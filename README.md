# User Account Order History for Concrete CMS Community Store

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

An addition to the concrete5 community store https://github.com/concretecms-community-store/community_store) that
shows a user's purchase history on their account. As with the community store, a Bootstrap based theme is assumed.

## Installation
You must install the community store before installing this addon.

Navigate to your packages folder, and then clone the repository

```git clone https://github.com/JeRoNZ/community_store_order_history.git```

Then go to the dashboard and run the install.

## Theming
If you wish to theme the account/orders page, you will need to modify your application/config/app.php file like this:

```php
<?php

return array(
	'theme_paths'         => array(
		'/account'        => 'theme_handle',
		'/account/*'      => 'theme_handle',
	),
)
```

where theme_handle is the handle of your theme.
