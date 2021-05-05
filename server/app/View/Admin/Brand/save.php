<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>{{ time() }}</h1>
    <h1>{{ time() }}</h1>
    <h1>{{ time() }}</h1>
    <h1>{{ \Minimal\Facades\Config::get('wallet.money.name') }}</h1>
    <p>{{ $name }}</p>

    {{ if ($name == 'abc'): }}
        <p>{{ $name }}</p>
    {{    elseif ($name == 'efg'): }}
        <p>{{ $name }}</p>
    {{else: }}
        <p>{{ $name }} 哈哈 {{ $name }}</p>
    {{ endif; }}

    {{ switch ($name): }}
    {{     case 'abc': }}
        <p>{{ $efg }}</p>
    {{ case 'efg': }}
        <p>{{ $efg }}</p>
    {{ default : }}
        <p>{{ $other }}</p>
    {{ endswitch; }}


    {{ foreach($list as $key => $item): }}
        <p>{{ $item }}</p>
    {{ endforeach; }}

    {{ for($i = 0;$i < 10;$i++): }}
        <p>{{ $i }}</p>
    {{ endfor; }}

    <p><?php echo $name; ?></p>
</body>
</html>