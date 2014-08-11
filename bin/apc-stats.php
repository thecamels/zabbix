<?php
if ($_SERVER["REMOTE_ADDR"] == "127.0.0.1") {
        switch($_GET['apc']) {
                case 'cache_info':
                        print(serialize(apc_cache_info('',true)));
                        break;
                case 'sma_info':
                        print(serialize(apc_sma_info()));
                        break;
                case 'user_cache_info':
                        print(serialize(apc_cache_info('user',true)));
                        break;
                default:
                        exit;
        }
}
?>