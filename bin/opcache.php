<?php

if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']){
    header('HTTP/1.1 401 Unauthorized', true, 401);
    exit;
}

$configuration = opcache_get_configuration();
$status = opcache_get_status(FALSE);

switch($_GET['item']) {
    // CONFIGURATION
    case 'version':
        print($configuration['version']['version']);
        break;
    case 'enable':
        print($configuration['directives']['opcache.enable']);
        break;
    case 'enable_cli':
        print($configuration['directives']['opcache.enable_cli']);
        break;
    case 'use_cwd':
        print($configuration['directives']['opcache.use_cwd']);
        break;
    case 'validate_timestamps':
        print($configuration['directives']['opcache.validate_timestamps']);
        break;
    case 'inherited_hack':
        print($configuration['directives']['opcache.inherited_hack']);
        break;
    case 'dups_fix':
        print(($configuration['directives']['opcache.dups_fix'] ? 1 : 0));
        break;
    case 'revalidate_path':
        print(($configuration['directives']['opcache.revalidate_path'] ? 1 : 0));
        break;
    case 'log_verbosity_level':
        print($configuration['directives']['opcache.log_verbosity_level']);
        break;
    case 'memory_consumption':
        print($configuration['directives']['opcache.memory_consumption']);
        break;
    case 'interned_strings_buffer':
        print($configuration['directives']['opcache.interned_strings_buffer']);
        break;
    case 'max_accelerated_files':
        print($configuration['directives']['opcache.max_accelerated_files']);
        break;
    case 'max_wasted_percentage':
        print($configuration['directives']['opcache.max_wasted_percentage']);
        break;
    case 'consistency_checks':
        print($configuration['directives']['opcache.consistency_checks']);
        break;
    case 'force_restart_timeout':
        print($configuration['directives']['opcache.force_restart_timeout']);
        break;
    case 'revalidate_freq':
        print($configuration['directives']['opcache.revalidate_freq']);
        break;
    case 'max_file_size':
        print($configuration['directives']['opcache.max_file_size']);
        break;
    case 'protect_memory':
        print(($configuration['directives']['opcache.protect_memory'] ? 1 : 0));
        break;
    case 'save_comments':
        print($configuration['directives']['opcache.save_comments']);
        break;
    case 'load_comments':
        print($configuration['directives']['opcache.load_comments']);
        break;
    case 'fast_shutdown':
        print($configuration['directives']['opcache.fast_shutdown']);
        break;
    case 'enable_file_override':
        print(($configuration['directives']['opcache.enable_file_override'] ? 1 : 0));
        break;
    case 'optimization_level':
        print($configuration['directives']['opcache.optimization_level']);
        break;

    // STATUS
    case 'used_memory':
        print($status['memory_usage']['used_memory']);
        break;
    case 'free_memory':
        print($status['memory_usage']['free_memory']);
        break;
    case 'wasted_memory':
        print($status['memory_usage']['wasted_memory']);
        break;
    case 'current_wasted_percentage':
        print($status['memory_usage']['current_wasted_percentage']);
        break;

    case 'buffer_size':
        print($status['interned_strings_usage']['buffer_size']);
        break;
    case 'isu.used_memory':
        print($status['interned_strings_usage']['used_memory']);
        break;
    case 'isu.free_memory':
        print($status['interned_strings_usage']['free_memory']);
        break;
    case 'number_of_strings':
        print($status['interned_strings_usage']['number_of_strings']);
        break;

    case 'num_cached_scripts':
        print($status['opcache_statistics']['num_cached_scripts']);
        break;
    case 'num_cached_keys':
        print($status['opcache_statistics']['num_cached_keys']);
        break;
    case 'max_cached_keys':
        print($status['opcache_statistics']['max_cached_keys']);
        break;
    case 'hits':
        print($status['opcache_statistics']['hits']);
        break;
    case 'oom_restarts':
        print($status['opcache_statistics']['oom_restarts']);
        break;
    case 'hash_restarts':
        print($status['opcache_statistics']['hash_restarts']);
        break;
    case 'manual_restarts':
        print($status['opcache_statistics']['manual_restarts']);
        break;
    case 'misses':
        print($status['opcache_statistics']['misses']);
        break;
    case 'blacklist_misses':
        print($status['opcache_statistics']['blacklist_misses']);
        break;
    case 'blacklist_miss_ratio':
        print($status['opcache_statistics']['blacklist_miss_ratio']);
        break;
    case 'opcache_hit_rate':
        print($status['opcache_statistics']['opcache_hit_rate']);
        break;
    default:
        exit;
}
?>