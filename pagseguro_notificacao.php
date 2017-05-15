    <?php

    /*
    * Fonte de consulta : https://pagseguro.uol.com.br/v2/guia-de-integracao/api-de-notificacoes.html
    *
    * Esta funcao e' chamada externamente pelo pagseguro
    * para avisar sobre as transacoes de vendas.
    * Sera recebido o seguinte post:
    * POST http://lojamodelo.com.br/notificacao HTTP/1.1
    * Host:pagseguro.uol.com.br
    * Content-Length:85
    * Content-Type:application/x-www-form-urlencoded
    * notificationCode=766B9C-AD4B044B04DA-77742F5FA653-E1AB24
    * notificationType=transaction
    *
    */

    /**
    * Função para conexão ao servidor do PagSeguro 
    * via Curl.
    * 
    * @access public
    * @param type $url
    * @param string $method GET com padrão
    * @param array $data
    * @param type $timeout 30
    * @param type $charset ISO
    * @return xml
    */
    function psn_curl_connection(
        $url, $method = 'GET', Array $data = null, 
        $timeout = 30, $charset = 'ISO-8859-1') {

        if (strtoupper($method) === 'POST') {
            $postFields    = ($data ? http_build_query($data, '', '&') : "");
            $contentLength = "Content-length: ".strlen($postFields);
            $methodOptions = Array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
            );
        } else {
            $contentLength = null;
            $methodOptions = Array(
                CURLOPT_HTTPGET => true
            );
        }

        $options = Array(
            CURLOPT_HTTPHEADER => Array(
                "Content-Type: application/x-www-form-urlencoded; charset=".$charset,
                $contentLength
            ),
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            //CURLOPT_TIMEOUT => $timeout
        );

        $options = ($options + $methodOptions);

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $resp  = curl_exec($curl);
        $info  = curl_getinfo($curl);// para debug
        $error = curl_errno($curl);
        $errorMessage = curl_error($curl);
        curl_close($curl);

        if ($error) {
            psn_log_message( $errorMessage );
            return false;
        } else {
            return $resp;
        }
    }

    /**
    * Rece um código de notificação do Pagseguro, conecta-se 
    * ao servidor do pagseguro e retorna informações extras 
    * sobre essa notificação.
    * 
    * @access public
    * @param string - código de notificação enviado pelo Pagseguro. 
    * Algo como 766B9C-AD4B044B04DA-77742F5FA653-E1AB24
    * @param string - email do cliente cadastrado no pagseguro
    * @param string - token do cliente para autenticação
    * @return array[code,status,reference,shippingType,shippingCost]
    */
    function psn_get_notification(
        $__notificationCode = null,
        $__email = null,
        $__token = null ) {

        # se não for passado um código, usará o $_POST que o PS envia
        if($__notificationCode === null && isset($_POST['notificationCode']))
            $__notificationCode = $_POST['notificationCode'];

        # validacoes basicas de entrada de dados 
        # temos um codigo de notificacao?
        if(!isset($__notificationCode)){
            psn_log_message( "Nenhum código de notificação recebido." );
            return false;}

        # recebemos um endereco de email e token a consulta?
        if( (!isset($__email)) || (!isset($__token)) ) {
            psn_log_message( "E-mail ou Token de autenticação não recebido." );
            return false;}

        # ex: https://ws.pagseguro.uol.com.br/v2/transactions/notifications/766B9C-AD4B044B04DA-77742F5FA653-E1AB24?email=suporte@lojamodelo.com.br&token=95112EE828D94278BD394E91C4388F20'
        $url = "https://ws.pagseguro.uol.com.br/v2/transactions/notifications/{$__notificationCode}?email={$__email}&token={$__token}";

        # conecta-se ao pagseguro
        $transaction = psn_curl_connection($url);

        # algo deu errado na autenticação
        if($transaction == 'Unauthorized'){
            psn_log_message(
                'Unauthorized - Erro ao consultar notificação "'.
                $__notificationCode.'" do PagSeguro.');
            return false;
        }

        # converte o xml recebido do PS para um objeto
        $xml = simplexml_load_string($transaction);

        # definindo dados de retorno
        $retorno = array();
        $retorno['code'] = (int)$xml->status;
        $retorno['status'] = $this->ps_stats((int)$xml->status);
        $retorno['reference'] = $xml->reference;
        $retorno['shippingType'] =  isset($xml->shipping->type) ? $xml->shipping->type : null ;
        $retorno['shippingCost'] =  isset($xml->shipping->cost) ? $xml->shipping->cost : null ;

        return $retorno;
    }

    /**
    * Função usada para realizar log em arquivo texto 
    * para uma possivel auditoria.
    * 
    * @param  string|array - informação a ser logada
    * @param  string - error_|notification_ será o inicio do nome 
    * do arquivo de log gravado. ex: "notification_2007-05_"
    * @return bool
    */
    function psn_log_message( $__message = null, $__tipo = 'error_' )
    {
        # validações basicas da entrada recebida
        if( is_null($__message) ) return false;
        if( is_array($__message) ) $__message = implode(" - ", $__message);
        if(!is_string($__message) ) return false;

        # o nome do nosso arquivo de log sera organizado por
        # Ano e mes. Ex: "error_2017-05_"
        date_default_timezone_set( 'America/Sao_Paulo' );
        $__curr_mes_e_ano = date( 'Y-m' );

        # aproveitamos para indicar o momento em que aconteceu 
        # a gravação do evento.
        $__message = date('Y-m-d H:i:s') . " (pagseguro_notificacao.php): " . $__message ; 

        # obtemos um caminho completo para a nossa
        # pasta de log, e o nosso arquivo a ser gravado
        $__filename = getcwd() . DIRECTORY_SEPARATOR . 'logs' .
            DIRECTORY_SEPARATOR . strtolower( "{$__tipo}{$__curr_mes_e_ano}_" );

        # efetivamos a gravação dos dados no arquivo de LOG
        file_put_contents( $__filename, $__message . PHP_EOL, FILE_APPEND);
        return true;
    }

    /**
    * Atualiza status de uma venda. 
    *
    *  -----------------------------
    * Em SQL essa consulta equile a um :
    * UPDATE vendas
    * SET `status_venda` = '$status_venda',
    *     `status` = '$__notificationCode', 
    *     `active` = 0
    * WHERE `key` = '$key' ; ";
    *-----------------------------
    *
    * @param string $key - valor unico que identifica uma venda
    * @param int $__notificationCode - codigo numerico do status do pagseguro
    * @param string $status_venda - frase do status
    * @return bool
    */
    function psn_updateStatus( $key = 0, $__notificationCode = 0, $status_venda = "Aguardando pagamento" ) {

        require_once 'database/database.php' ;

        $_dados = array( 
            'status_venda' => $status_venda , 
            'status' => $__notificationCode);

        # verifica se codigo recebido indica cancelamendo da compra.
        # Assim, damos baixa na venda e cancelamos a compra
        if($__notificationCode == 7) {
            $_dados['active'] = 0;
        }

        # atualiza tabela de vendas onde a coluna Key e' igual a 
        # Key referencia retornado pelo Pagseguro.
        return Database::update_where( "vendas", array('key' => $key), $_dados );
    }

    /**
    * Uma vez que o cliente pode escolher a forma de envio 
    * no ato do pagamento no Pagseguro. Você só sera informado 
    * sobre qual método o cliente escolheu, quando o Pagseguro 
    * lhe retornar uma notificação sobre essa escolha.
    *
    * Essa função serve para atualizar a tabela de vendas, 
    * e a coluna método de envio para o valor 
    * que o cliente escolheu.
    * 
    * @param  integer - referencia de uma venda
    * @param  string
    * @param  string  $valor_envio [description]
    * @return bool
    */
    function psn_updateEnvio( $key = 0, $forma_envio = null, $valor_envio = '0.00' )
    {
        require_once 'database/database.php' ;

        $_dados = array('valor_envio' => $valor_envio);
        if( isset($forma_envio) )
            $_dados['forma_envio'] = $forma_envio;

        return Database::update_where( "vendas", array('key' => $key), $_dados );
    }


    # DADOS DE CONFIGURAÇÕES
    # ----------------------
    # definindo configuração que serão usadas
    $config = new stdClass ;

    # Credenciais hipoteticas para testes com Pagseguro
    $config -> pagseguro_email = 'suporte@lojamodelo.com.br';
    $config -> pagseguro_token = '95112EE828D94278BD394E91C4388F20';


    # 1. ESTAMOS RECEBENDO UM POST DO PAGSEGURO?
    # ------------------------------------------
    # verificando se houve uma tentativa de POST nessa pagina
    if( !isset( $_POST['notificationCode'] ) ) return;

    # de posse de um codigo recebido via POST,
    # faz chamada ao pagseguro e recebe xml com demais dados
    $__code = $_POST['notificationCode'];


    # 2. BASEADO NO CODIGO RECEBIDO, TENTAMOS OBTER MAIS INFORMAÇÕES
    # --------------------------------------------------------------
    # fazemos uma chamada ao pagseguro para saber mais informacoes
    # sobre a notificação que ele nos enviou
    $_retorno = psn_get_notification( 
        $__code, 
        $config->pagseguro_email, 
        $config->pagseguro_token ) ;

    # se aconteceu um erro ao consultar o pagseguro
    # apenas matamos o processo.
    if($_retorno == false) exit();

    # Se o pagseguro nos retornou informacoes 
    # sobre uma determinada notificação, 
    # então, por via das duvidas guardando o retorno para auditoria
    psn_log_message( $_retorno, 'notification_' );


    # 3. BASEADO NA NOTIFICAÇÃO COMEÇAMOS A FAZER ATUALIZAÇÕES
    # --------------------------------------------------------
    # efetua ações para avisar o CMS o que esta 
    # acontecendo quanto ao status da venda
    psn_updateStatus(
        $_retorno['reference'], /* cod de ref para uso entre sistema de vendas e o pagseguro */
        $_retorno['code'],      /* codigo numerico que indica o status da transacao */
        $_retorno['status']     /* descricao texto do status */
    );

    # verifica se codigo e' 7 o que indica cancelamento ...
    # neste caso retorna itens da venda para o estoque
    if( $_retorno['code'] == 7 ){

        # identificação da venda que foi cancelada.
        # use o reference para saber qual venda se refere.
        $__identificacao_da_venda = $_retorno['reference'];

        # aqui você obtem todos os itens dessa venda

        # Aqui você insere seu código para que cada item
        # vendido fique disponivel novamente em estoque.

        # caso tenha mais ações necessárias para cancelar a venda
        # insira aqui.
    }

    # verificando se existe algo para atualizar 
    # a respeito do metodo de envio pelos correios
    if(isset($_retorno['shippingCost'])){
        # atualiza dados sobre o envio
        psn_updateEnvio(
            $_retorno['reference'],    /* codigo Key de referencia */
            $_retorno['shippingType'], /* tipo de envio PAC, SEDEX */
            $_retorno['shippingCost']  /* custo do envio */
        );
    }
