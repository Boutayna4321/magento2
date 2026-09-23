<?php
/**
 * Queue configuration for production deployment.
 * 
 * Defines which consumers should run and their configuration.
 * In production, start consumers via:
 *   bin/magento queue:consumers:start alpincommerce_autoinvoice_async
 *   bin/magento queue:consumers:start alpincommerce_partialinvoice_async
 *   bin/magento queue:consumers:start alpincommerce_creditmemo_async
 *   bin/magento queue:consumers:start alpincommerce_customercare_vip_async
 * 
 * Or use the cron-based approach below with --single-thread-mode
 */
return [
    'consumers' => [
        'alpincommerce_autoinvoice_async' => [
            'queue' => 'alpincommerce.autoinvoice.process',
            'max_messages' => 1000,
            'max_idle_time' => 3600,
        ],
        'alpincommerce_partialinvoice_async' => [
            'queue' => 'alpincommerce.partialinvoice.process',
            'max_messages' => 1000,
            'max_idle_time' => 3600,
        ],
        'alpincommerce_creditmemo_async' => [
            'queue' => 'alpincommerce.creditmemo.process',
            'max_messages' => 1000,
            'max_idle_time' => 3600,
        ],
        'alpincommerce_customercare_vip_async' => [
            'queue' => 'alpincommerce.customercare.vip.recalculate',
            'max_messages' => 1000,
            'max_idle_time' => 3600,
        ],
    ],
    'retry' => [
        'default' => [
            'max_retries' => 3,
            'retry_interval' => 10,
            'error_queue' => 'alpincommerce.dlq',
        ],
    ],
];
