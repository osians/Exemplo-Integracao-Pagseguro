<?php

	require_once 'classes/LIBVENDAS/Cart.class.php';
	$carrinho = new LIBVENDAS\Cart;
	$session = LIBVENDAS\Session::getInstance();


	# DADOS DO CLIENTE
	# ----------------
	# Uma vez que o foco deste codigo é demonstrar 
	# o uso da API do Pagseguro., não foi implementado
	# um sistema de cadastro e login de usuários.
	# Portanto, aqui eu uso um usuário hipotetico e 
	# estatico para preencher os dados que devem ser 
	# enviados ao Pagseguro.
	# Em seu Código, você deve obter os dados do Cliente
	# que esta realizando a compra aqui via SGBD.
	require_once 'database/database.php' ;
	$_tabela = Database::get( 'usuarios' ) ;

	# pegando o primeiro cliente da tabela
	$cliente = array_pop( $_tabela );

	# convertemos array para um objeto afim de ficar mais intuitivo manipular os dados.
	$cliente = json_decode(json_encode( $cliente ),FALSE) ;

    # verificando se ja setou forma de envio
    $_tmp = $session->dados_envio ;
    $__forma_de_envio = isset($_tmp['forma_envio']) ? $_tmp['forma_envio'] : 'Não Definido' ;

    # seta o botao de continuar
    $__enabled = (isset( $_tmp )&&count($_tmp) > 0 ) ? 'enabled' : 'disabled' ;

?>
	<?php include 'frontend_header.php'; ?>

	<h2>Finalizar Compra</h2>

    <?php
    	# verificando se temos uma compra em andamento 
    	# para este cliente.
        $total_items = $carrinho->total_items();
        ( $total_items > 0 ) or die( "Você ainda não adicionou produtos ao carrinho de compras." );
    ?>

    <h1>Confirmar Pedido: #97631 </h1>

    <h3>Confirme os dados da sua Compra para Proseguir</h3>
    <p>
        Confira abaixo a descrição detalhada da sua compra. Se tudo estiver correto, clique sobre o botão <b>Continuar</b> e
        você será direcionado ao Site do Pagseguro para efetuar o pagamento e calcular a taxa de envio para sua compra.
    </p>

    <h2>Descrição dos produtos </h2>

    <table class="sysv-confirmar-pedido">
    	<thead>
	    	<tr>
				<th>Pedido</th>
				<th>Qtd.</th>
				<th>Preço</th>
				<th>Sub-Total</th>
	    	</tr>
    	</thead>
    	
    	<tbody>
	    	<?php foreach ($carrinho->contents() as $items): ?>
	            <tr>
	              	<td>
	                	<?php echo $items['name'] . " - " . $items['options']['modelo_title']; ?>
	              	</td>
	              	<td><?=$items['qty']; ?></td>
	              	<td><?php echo $carrinho->format_number($items['price']); ?></td>
	              	<td>R$<?php echo $carrinho->format_number($items['subtotal']); ?></td>
	            </tr>
	    	<?php endforeach; ?>
    	</tbody>

    	<tfoot>
    		<tr>
    			<td colspan="5"><h3>Detalhes da Venda </h3></td>
    		</tr>
    		<tr>
    			<td colspan="3"><strong>Valor dos produtos:</strong></td>
    			<td>R$<?php echo $carrinho->format_number($carrinho->total()); ?></td>
    		</tr>
    		<tr>
    			<td colspan="3"><strong>Frete:</strong></td>
    			<td><b>Será calculado pelo Pagseguro</b></td>
    		</tr>
    		<tr>
    			<td colspan="3"><strong>Valor Total:</strong></td>
    			<td class="td-destaque"><b>R$<?php echo $carrinho->format_number($carrinho->total()); ?> + Frete</b></td>
    		</tr>
    		<tr>
    			<td colspan="3"><strong>Forma de Envio:</strong></td>
    			<td class="td-destaque"><b><?=$__forma_de_envio;?></b></td>
    		</tr>
    	</tfoot>
    </table>

    <br>

    <h1>Dados do Cliente</h1>

    <?php
        echo $cliente->nome . " " . $cliente->sobrenome . "<br>";
        echo $cliente->email;
        echo "<br>Documento : $cliente->cpf" ;

	    echo "<br>" ; 

        echo "<h2>Endereço de Entrega e Cobrança</h2>";
        echo "<p><label>Endereço: </label> {$cliente->endereco->endereco} </p>";
        echo "<p><label>Número: </label> {$cliente->endereco->numero} </p>";
        echo "<p><label>Bairro: </label> {$cliente->endereco->bairro} </p>";
        echo "<p><label>Cidade: </label> {$cliente->endereco->cidade} </p>";
        echo "<p><label>Estado: </label> {$cliente->endereco->estado} </p>";
        echo "<p><label>CEP: </label> {$cliente->endereco->cep} </p>";
        echo "<p><label>Referência: </label> {$cliente->endereco->referencia} </p>";
    ?>

    <br><hr /><br>

    <form method="post" action="concluir_pedido.php" name="formas_de_pagamento">
	    <?php
	        # apresenta mensagem para que o cliente escolha a forma de envio
	        if( $__enabled == 'disabled' )
	        	echo "Você não definiu qual a forma de Envio da sua compra. Por favor, 
	        		clique no link a seguir e selecione uma das formas de envio disponíveis.
	        		<a class='link-setar-forma-envio' href='carrinho.php'>Forma de Envio</a><br><br><hr>";
	    ?>

	    <p>
        	Ao clicar em <b>Continuar</b> você será direcionado ao site do <b>Pagseguro</b> para efetuar o seu pagamento, e também calcular os custos de envio da sua compra.
        </p>

        <center>
        	<input type="submit" class="btn-fc-<?=$__enabled;?>" name="continuar" id="fechar_pedido" value="Concluir" <?=$__enabled;?> />
    	</center>
    </form>

    <br>


<?php include 'frontend_footer.php'; ?>