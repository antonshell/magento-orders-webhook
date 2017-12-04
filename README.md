# Magento 2 Orders Webhooks

Sends webhooks when magento order created/updated/deleted.

Saves webhooks to orders_webhooks_items table

# Configure

1 . Setup module - Create folder app/code/Antonshell/OrdersWebhooks. Copy all files there.

2 . Enable module

```
bin/magento module:enable Antonshell_OrdersWebhooks
```

3 . Setup upgrade 

```
bin/magento setup:upgrade
```

4 . Configure webhook url

```
bin/magento orders-webhook:configure --webhook-url=http://127,0,0,1/webhooks/handler.php
```

5 . Configure webhook secret token

```
bin/magento orders-webhook:configure --webhook-token=6jYoHgta71kh24si
```

6 . Need to implement webhook handler. See below

# Usage

1 . Create new order or update existing. Anyway, new order will be created

2 . Ensure that webhook is created(optional).

```
SELECT * FROM magento_220.orders_webhooks_items;
```

3 . Send webhooks

```
bin/magento orders-webhook:send
```

# Webhook handler

Need to implement webhook handler. It would be something like that:

```
<?php

$webhookKey = 'yWjfqTzDqtEjTCt0wBXM';

$payload = file_get_contents('php://input');
$data = json_decode($payload,true);


if(isset($data['webhook_meta']['verification_token']) && $data['webhook_meta']['verification_token'] == $webhookKey){
    $filename = __DIR__ . '/' . date('Y-m-d_H:i:s') . '_' . uniqid() . '.json';
    file_put_contents($filename,$payload);

    $result = ['success' => 'true', 'file' => $filename];
    echo json_encode($result);
    die();
}
else{
    $result = ['success' => 'false', 'error' => 'Invalid token'];
    echo json_encode($result);
    die();
}
```