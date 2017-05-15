<?php 
    $_status_pagseguro = array(
        10001 => 'Email is required.',
        10002 => 'Token is required.',
        10003 => 'Email invalid value.',
        11001 => 'receiverEmail is required.',
        11002 => 'receiverEmail invalid length: {0}.',
        11003 => 'receiverEmail invalid value.',
        11004 => 'Currency is required.',
        11005 => 'Currency invalid value: {0}',
        11006 => 'redirectURL invalid length: {0}',
        11007 => 'redirectURL invalid value: {0}',
        11008 => 'reference invalid length: {0}',
        11009 => 'senderEmail invalid length: {0}',
        11010 => 'senderEmail invalid value: {0}',
        11011 => 'senderName invalid length: {0}',
        11012 => 'senderName invalid value: {0}',
        11013 => 'senderAreaCode invalid value: {0}',
        11014 => 'senderPhone invalid value: {0}',
        11015 => 'ShippingType is required.',
        11016 => 'shippingType invalid type: {0}',
        11017 => 'shippingPostalCode invalid Value: {0}',
        11018 => 'shippingAddressStreet invalid length: {0}',
        11019 => 'shippingAddressNumber invalid length: {0}',
        11020 => 'shippingAddressComplement invalid length: {0}',
        11021 => 'shippingAddressDistrict invalid length: {0}',
        11022 => 'shippingAddressCity invalid length: {0}',
        11023 => 'shippingAddressState invalid value: {0}, must fit the pattern: \w\{2\} (e. g. "SP")',
        11024 => 'Itens invalid quantity.',
        11025 => 'Item Id is required.',
        11026 => 'Item quantity is required.',
        11027 => 'Item quantity out of range: {0}',
        11028 => 'Item amount is required. (e.g. "12.00")',
        11029 => 'Item amount invalid pattern: {0}. Must fit the patern: \d+.\d\{2\}',
        11030 => 'Item amount out of range: {0}',
        11031 => 'Item shippingCost invalid pattern: {0}. Must fit the patern: \d+.\d\{2\}',
        11032 => 'Item shippingCost out of range: {0}',
        11033 => 'Item description is required.',
        11034 => 'Item description invalid length: {0}',
        11035 => 'Item weight invalid Value: {0}',
        11036 => 'Extra amount invalid pattern: {0}. Must fit the patern: -?\d+.\d\{2\}',
        11037 => 'Extra amount out of range: {0}',
        11038 => 'Invalid receiver for checkout: {0}, verify receiver\'s account status.',
        11039 => 'Malformed request XML: {0}.',
        11040 => 'maxAge invalid pattern: {0}. Must fit the patern: \d+',
        11041 => 'maxAge out of range: {0}',
        11042 => 'maxUses invalid pattern: {0}. Must fit the patern: \d+',
        11043 => 'maxUses out of range.',
        11044 => 'initialDate is required.',
        11045 => 'initialDate must be lower than allowed limit.',
        11046 => 'initialDate must not be older than 6 months.',
        11047 => 'initialDate must be lower than or equal finalDate.',
        11048 => 'search interval must be lower than or equal 30 days.',
        11049 => 'finalDate must be lower than allowed limit.',
        11050 => 'initialDate invalid format, use \'yyyy-MM-ddTHH:mm\' (eg. 2010-01-27T17:25).',
        11051 => 'finalDate invalid format, use \'yyyy-MM-ddTHH:mm\' (eg. 2010-01-27T17:25).',
        11052 => 'page invalid value.',
        11053 => 'maxPageResults invalid value (must be between 1 and 1000).',
        11057 => 'senderCPF invalid value: {0}'
    );
?>

<?php include 'frontend_header.php'; ?>

<br />

<h2 class="pages-h2">Página de Erro</h2>

<h1> Problemas ao realizar a Compra </h1>

<h3>Ocorreu um erro durante o processamento de sua compra com o Pagseguro. </h3><br />

<b>Código do Erro</b><br />
<?php 
    if( isset($_GET['error']) ):
        $cod  = $_GET['error'];
        $desc = isset($_status_pagseguro[$cod]) ? $_status_pagseguro[$cod] : "Undefined";
        echo "{$cod} - {$desc}";
    else:
        echo "0 - Unauthorized";
    endif;
?>
<br />
<br />
<p>Para ajudar-nos a corrigir esse erro envie um e-mail para <b>contato@nomesuadaempresa.com.br</b> informando como o erro aconteceu.</p>


<?php include 'frontend_footer.php'; ?>