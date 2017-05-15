<?php

	# AREA PARA DECLARAÇÃO DE FUNÇÕES UTEIS
	# -------------------------------------
	# base_url() obtem a URL principal do site
	# @return string
	function base_url() {
	    $pathInfo = pathinfo($_SERVER['PHP_SELF']);
	    $protocolo = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https://'?'https://':'http://';
	    return $protocolo.$_SERVER['HTTP_HOST'].$pathInfo['dirname']."/";
	}

	# INICIALIZANDO OBJETO CART
	# -------------------------
	require_once 'classes/LIBVENDAS/Cart.class.php';
	$carrinho = new LIBVENDAS\Cart;

	# INICIALIZANDO SESSAO
	# --------------------
	$session = LIBVENDAS\Session::getInstance();

	# --------------------------------
	# [1]. INICIANDO BANCO DE DADOS &
	# --------------------------------
	# [1.1] IDENTIFICANDO O CLIENTE
	# --------------------------
	# Obtendo novamente dados hipoteticos de
	# um cliente logado no sistema.
	# O ideal aqui, e' usar os dados da Sessão para
	# identificar o cliente logado e obter todas as
	# informações do banco de dados.
	# Dados Gerais, endereços e etc.
	require_once 'database/database.php' ;
	$_tabela_clientes = Database::get( 'usuarios' ) ;

	# pegando o primeiro cliente da tabela
	$cliente = array_pop( $_tabela_clientes );

	# convertemos array para um objeto afim de ficar mais intuitivo manipular os dados.
	$cliente = json_decode(json_encode( $cliente ),FALSE) ;


	# --------------------------
    # [2]. CLIENTE ESTA LOGADO ?
    # --------------------------
    # Verifique aqui sempre se o usuario esta
    # devidamente logado no sistema e tem sessão ativa.
    # Caso não estiver, direcionar o cliente para
    # a pagina de login ou cadastro.
    $idcliente = $cliente -> id ;

    if( ! $idcliente ){
        # direcionar aqui usuario para a página de Login
        exit;
    }

	# ----------------------------------------------
    # [3]. GRAVANDO DADOS DA VENDA NO BANCO DE DADOS
    # ----------------------------------------------
    # Você deve ter uma tabela no seu banco de dados que
    # guarda todos os dados referentes a venda. Nesse caso,
    # o código abaixo visa gerar todos os dados necessarios
    # para identificar essa venda no seu sistema.

    # @var string - codigo para identificação dessa venda no sistema
    # Aqui, gerando um codigo com 24 caracteres: "Ano atual + 20 caracteres"
    # Esse Codigo servira de referencia para o Pagseguro identificar a
    # sua Venda.
    $key = date('Y') .
    	strtoupper(substr(str_shuffle(str_repeat(
			$x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
			ceil(20/strlen($x)))),1,20));

    # @var integer - Codigo numerico que identifica a forma de envio.
    $forma_envio = $session->get( 'dados_envio' );

    # idcliente    - ID unica do cliente.
    # key          - Serve de referencia para o PagSeguro
    # pscode       - Codigo que o Pagseguro envia para identificar transação
    # active       - (opcional) informa que o registro esta ativo no Banco de dados
    # forma_pagto  - Definindo a forma de pagamento
    # forma_envio  - Determina a forma de envio da Mercadoria, PAC, SEDEX, etc;
    # valor_envio  - Sera definido no Pagseguro
    # valor_total  - Valor total da Venda
    # status_venda - Status Padrão
    $dados_venda = array(
        'idcliente'    => $idcliente,
        'key'          => $key,
        'pscode'       => null,
        'active'       => 1,
        'forma_pagto'  => 'Pagseguro',
        'forma_envio'  => ((!isset($forma_envio['cod_servico'])) ? '3' : $forma_envio['cod_servico']), /* 3 indica que sera decidido no pagseguro */
        'valor_envio'  => '0.00',
        'valor_total'  => $carrinho->total(),
        'status_venda' => 'Aguardando pagamento'
    );

    # salvando dados da venda no banco de dados...
    # aqui, realize o processo de salvar os dados na sua tabela de vendas
    $idvenda = Database::save( 'vendas', $dados_venda );


    # ----------------------------------------------
    # [4]. GRAVANDO ITEMS DA VENDA NO BANCO DE DADOS
    # ----------------------------------------------
    # Esta é a parte em que você registra todos os itens vendidos
    # na sua tabela de itens de venda.
    $_produtos = array();
    foreach ($carrinho->contents() as $items):

        $total = $items['qty'] * $items['price'];
        $item = array(
           'idvenda' => $idvenda,
           'item_id' => $items['id'],
           'title'   => $items['name'],
           'modelo'  => $items['options']['modelo'],
           'modelo_title' => $items['options']['modelo_title'],
           'qtd'     => $items['qty'],
           'preco'   => $items['price'],
           'total'   => $total,
           'peso'    => $items['options']['peso']
        );

        $_produtos[] = $item;

    endforeach;

    # salvando os itens vendidos no banco de dados
    Database::save( 'vendas_itens', $_produtos );


    # --------------------------------------------------
    # [5]. INICIANDO PROCESSO DE TRANSAÇÃO COM PAGSEGURO
    # --------------------------------------------------
	# Carregando os dados de Configurações
    # setando as configuracoes necessarias para integração
    $config = new stdClass;

    # dados da sua empresa
    $config -> empresa = 'SUA EMPRESA';
    $config -> empresa_mail = 'email_contato_sua_empresa@suaempresa.com.br';
    $config -> empresa_fone_01 = "12 4321-0987";
    $config -> empresa_fone_02 = "12 7654-0987";
	$config -> url_facebook = "javascript:void(0);" ;
	$config -> url_blogger = "javascript:void(0);" ;
	$config -> url_site = "javascript:void(0);" ;

	# Email do responsavel pela criacao e manutencao da integracao ao pagseguro
	$config -> dev_email = 'sans.pds@gmail.com';

	# Credenciais hipoteticas para testes com Pagseguro
	$config -> pagseguro_email = 'teste@lojaexemplo.com.br';
	$config -> pagseguro_token = '2013VMY38LWO37WRE5DGR99SDFE4ER72';

	# URL do Pagseguro a ser chamada com os dados da Venda
    $config -> url = 'https://ws.pagseguro.uol.com.br/v2/checkout/?email=' . $config -> pagseguro_email . '&token=' . $config -> pagseguro_token;

    # construindo o arquivo XML a ser enviado ao Pagseguro.
    # esse arquivo contem detalhes sobre a venda que são
    # importantes para que o sistema do Pagseguro calcule
    # corretamente os valores a serem pagos pelo cliente.
    $xml = '<?xml version="1.0" encoding="ISO-8859-1" standalone="yes"?>
            <checkout>
            <currency>BRL</currency>
            <redirectURL>' . base_url() . 'confirmacao</redirectURL>';

    # Aqui, informamos ao pagseguro sobre os itens que foram vendidos.
    # Enviando apenas algunas informações básicas.
    $xml .= "<items>";
        foreach( $_produtos as $produto ):
            $xml .= "
                <item>
                    <id>{$produto['item_id']}</id>
                    <description>" . utf8_decode( $produto['title'] ) . " - " . utf8_decode( $produto['modelo_title'] ) . "</description>
                    <amount>{$produto['preco']}</amount>
                    <quantity>{$produto['qtd']}</quantity>
                    <weight>{$produto['peso']}</weight>
                </item>";
        endforeach;
    $xml .= "</items>";

    # Informando ao Pagseguro nossa Chave de Referencia da Venda
    # Essa chave, serve para sabermos qual das nossas vendas
    # O Pagseguro recebeu o pagamento.
    $xml .= "<reference>$key</reference>";

    # Nessa seção do arquivo XML precisamos informar
    # os dados do Cliente que esta realizando a compra.
    $xml .= "<sender>
            <name>" . utf8_decode($cliente -> nome) . " " . utf8_decode($cliente -> sobrenome) . "</name>
            <email>$cliente->email</email>";

            # Setando informação sobre telefone
            # O pagseguro exige que os sejam enviados apenas numeros de
            # telefones sem pontos ou traços.
            $__fone     = trim( preg_replace( "/[^0-9]+/i", "", $cliente -> fone ) );
            $__fone_cod = trim( preg_replace( "/[^0-9]+/i", "", $cliente -> fone_cod ) );

            if(isset($__fone)){
                $xml .="<phone>
                        <areaCode>$__fone_cod</areaCode>
                        <number>$__fone</number>
                    </phone>";
            }
    $xml .= "</sender>";

    # setando tipo de envio Sedex(2), pac(1) ou retirada na loja(999), ou nao especificado(3)
    $__cod_envio = $forma_envio['cod_servico'];

    # Se a forma de envio foi definida como "retirada na loja (999)"
    # entao nao precisamos mandar os dados de endereço do cliente ao pagseguro
    if( $__cod_envio != '999' ){

    	# retira caracteres não numericos do cep
        $__cep    = trim( preg_replace( "/[^0-9]+/i", "", $cliente->endereco->cep ) );
        $__state  = strtoupper( $cliente->endereco->estado );
		$__complement = substr( $cliente->endereco->referencia,0, 35 );

		# setando o XML com dados de Endereço do Cliente
		# para o correto calculo dos dados de venda.
        $xml .= "<shipping>
            <type>$__cod_envio</type>
            <address>
                <street>" . utf8_decode($cliente->endereco->endereco). "</street>
                <number>{$cliente->endereco->numero}</number>
                <complement>" . utf8_decode($__complement) . "</complement>
                <district>" . utf8_decode($cliente->endereco->bairro)."</district>
                <postalCode>{$__cep}</postalCode>
                <city>" . utf8_decode($cliente->endereco->cidade) . "</city>
                <state>{$__state}</state>
                <country>BRA</country>
            </address>
        </shipping>";
    }

    # Concluindo e fechando o arquivo XML
    $xml .= "</checkout>";

    # AUDITORIA
    # ---------
    # Existem momentos em que é necessario fazer auditoria
    # dos dados enviados ao pagseguro. Portanto, a linha
    # abaixo, tem o objetivo de registrar um log da venda
    # efetuada.
    # [!IMPORTANTE]: Caso opte por fazer o log desse XML
    # tenha certeza de armazena-lo num local privado no
    # seu servidor para evitar acesso não autorizado.
    $__filepath = getcwd() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . strtolower( "ps_{$idvenda}_" );
	file_put_contents( $__filepath, $xml ) ;


	# -------------------------------------------
	# [6]. FAZENDO REQUISIÇÃO A API DO PAGSEGURO
	# -------------------------------------------
	# Essa seção do codigo usa a tecnologia CURL
	# para fazer um POST dos dados no serviço do Pagseguro.
    $curl = curl_init( $config -> url );
    # configuurando curl para nao verificar certificados ssl
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	# avisando ao curl para retornar a resposta do pagseguro
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    # informando ao curl o tipo de dado a ser transportado
    curl_setopt($curl, CURLOPT_HTTPHEADER, Array('Content-Type: application/xml; charset=ISO-8859-1'));
    # setando o xml a ser transportado pelo CURL
    curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
    # exeutando o CURL e recebendo resposta do pagseguro
    $xml = curl_exec( $curl );


    # AUDITORIA
    # ---------
    # Quando fizermos um post com CURL
    # o Pagseguro nos retornará um XML de resposta
    # que iremos registrar tambem no servidor.
    $__filepath = getcwd() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . strtolower( "rs_{$idvenda}_" );
	file_put_contents( $__filepath, $xml ) ;


	# VERIFICAÇÃO DE ERRO 01
	# ----------------------
    # unalthorized indica um erro com token ou email
    if($xml == 'Unauthorized'){
        # Insira seu código avisando que o sistema está com problemas,
        # sugiro enviar um e-mail avisando para alguém fazer a manutenção
        # redireciona usuario para uma pagina que indica o erro com a venda
        # pode ser interessante desfazer a venda nesse caso
        header( 'Location:' . base_url() . 'paginadeerro.php' );
        exit; # Matando o processo
    }

	# fechando o CURL
    curl_close( $curl );

    # transformando o XML recebido em um objeto
    $xml = simplexml_load_string( $xml );

    # VERIFICAÇÃO DE ERRO 02
    # ----------------------
    if(count($xml->error) > 0){
        # Insira seu código avisando que o sistema está com problemas,
        # sugiro enviar um e-mail avisando para o desenvolvedor,
        # talvez seja útil enviar os códigos de erros.
        # carregando dados de configuracao do pagseguro
        # redirecionando cliente para a pagina de erros
        header( 'Location:' . base_url() . "paginadeerro.php?error=" . $xml->error->code );
        exit;//Mantenha essa linha
    }

    # se nao ocorreu erro guarda codigo de transacao na tabela de vendas
	Database::update( "vendas", $idvenda, array( 'pscode' => $xml->code ) );

	# -----------------------------------------
    # [7]. ENVIANDO EMAIL DE SUCESSO NA COMPRA
	# -----------------------------------------
	# O codigo abaixo visa informar o cliente de 
	# que a compra dele foi registrada com sucesso.
	# 
    $assunto = "Confirmação de Pedido";
    $titulo = " ";
    $nome_destinatario = "$cliente -> nome $cliente->sbnome";

    $resumo = 
    "Esse e-mail visa informar que registramos sua compra com sucesso em nosso sistema. 
    Obrigado por Comprar conosco da {$config->empresa}!";

    $conteudo = "- Seu pedido será postado imediatamente após a compensação de pagamento. <br />
    - Para pagamento por Depósito ou Transferência o comprovante deve ser enviado
    para <b>{$config->empresa_mail}</b> ou através do seu Painel de Controles.<br /><br />

    Os produtos são reservados até a data de vencimento do seu pedido.<br /><br />

    No dia da postagem, será enviado um email automático com o número de registro 
    de sua entrega, juntamente com um link, para que você possa acompanhar a entrega. 
    As informações de rastreamento serão visualizadas após às 19h.<br /><br /> 
    - Para exclarecer qualquer dúvida entre em contato conosco através de um dos 
    meio de comunicação que seguem abaixo.";

    $nota = "Para acompanhar o andamento de sua compra, você pode utilizar seu 
    Painel de Controles <a href='".base_url()."cliente/login'>clicando aqui</a>.";

    $unsubscribe = base_url() . "main/unsubscribe";	
    $termos = base_url() . "termos-de-uso";
    $privaciade = base_url() . "politica-de-privacidade";

    $tpl = file_get_contents( "email_template.html" );

    $tpl = str_replace( "#nome_da_empresa#", $config -> empresa, $tpl );
    $tpl = str_replace( "#title#", $titulo, $tpl );
    $tpl = str_replace( "#codigo#", $key, $tpl  );
    $tpl = str_replace( "#resumo#", $resumo, $tpl );
    $tpl = str_replace( "#conteudo#", $conteudo, $tpl );
    $tpl = str_replace( "#nota#", $nota, $tpl );
    $tpl = str_replace( "#fone_01#", $config -> empresa_fone_01, $tpl );
    $tpl = str_replace( "#fone_02#", $config -> empresa_fone_02, $tpl );
    $tpl = str_replace( "#email#", $config -> empresa_mail, $tpl );
    
    $tpl = str_replace( "#endereco_facebook#", $config -> url_facebook, $tpl );
    $tpl = str_replace( "#endereco_blogger#", $config -> url_blogger, $tpl );
    $tpl = str_replace( "#endereco_site#", $config -> url_site, $tpl );

    $tpl = str_replace( "#nome_destinatario#", $nome_destinatario, $tpl );
    $tpl = str_replace( "#lnk_unsubscribe#", $unsubscribe, $tpl );
    $tpl = str_replace( "#lnk_privacidade#", $privaciade, $tpl );
    $tpl = str_replace( "#lnk_termos#", $termos, $tpl );

	# envia o email para o cliente avisando da confirmacao do pedido
	$__headers  = "MIME-Version: 1.1" . "\n";
	$__headers .= "Content-type: text/html; charset=UTF-8" . "\n";
	$__headers .= 'From: <'.$config->empresa_mail.'>' . "\n";
	$__headers .= 'Cc: '. $config->empresa_mail . "\n";

    if( ! mail( $cliente->email, $assunto, $tpl, $__headers ,"-r".$config->empresa_mail ) ){
        $__headers .= "Return-Path: " . $config->empresa_mail . "\n";
        mail($cliente->email, $assunto, $tpl, $__headers );
    }

	# -----------------------------------------------
	# [8]. FINALIZANDO PROCESSOS E LIMPANDO RECURSOS
    # -----------------------------------------------
	# limpando carrinho de compras
    $carrinho->destroy();

	# se esta OK, entao redireciona comprador para o pagseguro
	# para realizar o pagamento da compra
    redirect( 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=' . $xml->code, 'refresh' );
