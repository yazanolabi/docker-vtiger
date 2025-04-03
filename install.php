<?php
/**
 * Vtiger Installation Script.
 *
 * @author Francesco Bianco <bianco@javanile.org>
 */

date_default_timezone_set('America/Los_Angeles');

define('VT_VERSION', '8.3.0');
define('DB_TYPE', 'mysqli');
define('DB_HOST', 'database');
define('DB_PORT', '3306');
define('DB_NAME', 'vtigercrm_2025');
define('DB_USER', 'root');
define('DB_PASS', 'changeit');
define('DB_USER_ROOT', 'root');
define('DB_PASS_ROOT', 'changeit');

require_once '/var/www/html/vendor/autoload.php';

use Javanile\HttpRobot\HttpRobot;

echo "[vtiger] Testing installation...\n";

echo '[vtiger] Database params: '.DB_TYPE.' '.DB_HOST.' '.DB_PORT.' '.DB_NAME.' '.DB_USER.' '.DB_PASS."\n";
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if (mysqli_connect_errno()) {
    echo '[vtiger] Database error: '.mysqli_connect_errno().' - '.mysqli_connect_error()."\n";
    exit(1);
}

$robot = new HttpRobot([
    'base_uri' => 'http://crm.mabecenter.org/',
    'cookies'  => true,
]);

/**
 * Get session token
 */
echo "[vtiger] (#1) Get session token";
$values = $robot->get('index.php?module=Install&view=Index&mode=Step4', ['__vtrftk', '@text']);
echo " -> token: '{$values['__vtrftk']}'\n";
if (version_compare(VT_VERSION, '7.0.0', '>=')) {
    if (empty($values['__vtrftk'])) {
        echo " -> [ERROR] Session token not found\n";
        echo $values['@text'];
        exit(1);
    }
}

/**
 * Submit installation params
 */
echo "[vtiger] (#2) Sending installation parameters";
$values = $robot->post(
    'index.php',
    [
        '__vtrftk'         => $values['__vtrftk'],
        'module'           => 'Install',
        'view'             => 'Index',
        'mode'             => 'Step5',
        'db_type'          => DB_TYPE,
        'db_hostname'      => DB_HOST,
        'db_username'      => DB_USER,
        'db_password'      => DB_PASS,
        'db_name'          => DB_NAME,
        'db_root_username' => DB_USER_ROOT,
        'db_root_password' => DB_PASS_ROOT,
        'currency_name'    => 'USA, Dollars',
        'admin'            => 'admin',
        'password'         => 'admin',
        'retype_password'  => 'admin',
        'firstname'        => '',
        'lastname'         => 'Administrator',
        'admin_email'      => 'vtiger@localhost.lan',
        'dateformat'       => 'dd-mm-yyyy',
        'timezone'         => 'America/Los_Angeles',
    ],
    ['__vtrftk', 'auth_key', '@text']
);
echo " -> form-token: '{$values['__vtrftk']}' auth-key: '{$values['auth_key']}'\n";

echo "[vtiger] (#3) Confirm installation parameters";
$values = $robot->post(
    'index.php',
    [
        '__vtrftk' => $values['__vtrftk'],
        'auth_key' => $values['auth_key'],
        'module'   => 'Install',
        'view'     => 'Index',
        'mode'     => 'Step6',
    ],
    ['__vtrftk', 'auth_key', '@text']
);
echo " -> form-token: '{$values['__vtrftk']}' auth-key: '{$values['auth_key']}'\n";

/**
 * Selecting industry
 */
echo "[vtiger] (#4) Selecting industry";
$values = $robot->post(
    'index.php',
    [
        '__vtrftk' => $values['__vtrftk'],
        'auth_key' => $values['auth_key'],
        'module'   => 'Install',
        'view'     => 'Index',
        'mode'     => 'Step7',
        'industry' => 'Accounting',
    ],
    ['__vtrftk', '@text']
);

echo " -> form-token: '{$values['__vtrftk']}'\n";
if (version_compare(VT_VERSION, '7.0.0', '>=')) {
    if (empty($values['__vtrftk'])) {
        echo " -> [ERROR] install error on industry selector\n";
        $mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $error = mysqli_connect_error();
        $result = mysqli_query($mysqli, "SHOW TABLES");
        while ($table = mysqli_fetch_row($result))  {
            echo "Table: $table[0]\n";
        }
        echo $values['@text'];
        if (file_exists('/var/lib/vtiger/logs/php.log')) {
            echo file_get_contents('/var/lib/vtiger/logs/php.log');
        }
        #exit(1);
    }
}

/**
 * First login seems required only for >7
 */
echo "[vtiger] (#5) First login";
$values = $robot->post(
    'index.php?module=Users&action=Login',
    [
        '__vtrftk' => $values['__vtrftk'],
        'username' => 'admin',
        'password' => 'admin',
    ],
    ['__vtrftk', '@text']
);

/**
 * Select crm modules
 */
echo "\n[vtiger] (#6) Select modules and packages";
$values = $robot->post(
    'index.php?module=Users&action=SystemSetupSave',
    [
        '__vtrftk'            => $values['__vtrftk'],
        'packages[Tools]'     => 'on',
        'packages[Sales]'     => 'on',
        'packages[Marketing]' => 'on',
        'packages[Support]'   => 'on',
        'packages[Inventory]' => 'on',
        'packages[Project]'   => 'on',
    ],
    ['__vtrftk', '@text']
);

echo " -> form-token: '{$values['__vtrftk']}'\n";

// =================================================================
// Select Modules
/*
$modules = [
    'Documents' => false,
];
foreach ($modules as $module => $status) {
    echo "[vtiger] ".($status?'enable':'disable')." module '${module}': ";
    $resp = $robot->post(
        'index.php',
        [
            '__vtrftk' => $vtrftk,
            'module' => 'ModuleManager',
            'parent' => 'Settings',
            'action' => 'Basic',
            'mode' => 'updateModuleStatus',
            'forModule' => $module,
            'updateStatus' => $status,
        ]
    );
    echo trim($resp)."\n";
}
*/