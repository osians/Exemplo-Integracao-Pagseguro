<?php

	# INICIALIZANDO OBJETO CART
	# -------------------------
	# adicionando a classe responsavel por gerenciar
	# o carrinho de compras.
	require_once 'classes/LIBVENDAS/Cart.class.php';
	$carrinho = new LIBVENDAS\Cart;


	# INICIALIZANDO SESSAO
	# --------------------
	# vamos fazer uso do objeto que manipula
	# sessao. Logo, pegamos sua instancia que
	# ja foi criada anteriormente pela classe
	# Cart.
	$session = LIBVENDAS\Session::getInstance();


	# VERIFICANDO SOLICITACAO DE REMOÇÃO DE ITEM
	# ------------------------------------------
	# Verifica se o cliente solicitou a remoção
	# de algum item do carrinho.
	# A ação vem contida na URL.
	if( isset($_GET['remover']) ):
        $data = array( 'rowid' => $_GET['remover'], 'qty' => 0 );
        $carrinho->update( $data );
	endif;


	# VERIFICANDO SOLICITACAO DE ATUALIZAÇÂO
	# --------------------------------------
	# Caso o cliente decida atualizar o numero
	# de itens no carrinho, esse trecho de codigo
	# é responsavel por processar os novos números.
	if($_POST):
        $data = array();
        foreach( $_POST as $row )
            $data[] = array('rowid'=> $row['rowid'],'qty'  => $row['qty']);
        $carrinho->update( $data );
	endif;


	# SETADNO O TIPO DE ENVIO DA ENCOMENDA
	# ------------------------------------
	# @var Array - Servicos de entrega disponiveis
    $_servicos_de_entrega = array(
    	'999' => 'RETIRADA NA LOJA',
    	'1'   => 'PAC',
    	'2'   => 'SEDEX',
    	'3'   => 'Não Especificado');

	# O trecho abaixo verifica se o cliente escolheu
	# a forma de envio da encomenta. SEDEX, PAC, etc.
	if( isset($_GET['forma_envio']) ):

		# @var int - codigo numerico do servico de entrega
    	$__cod_servico = preg_replace( '/[^\d-]+/','', $_GET['forma_envio'] ) ;

        # verificando se servico solicitado existe
        if(isset($_servicos_de_entrega[$__cod_servico])):
	        # o valor sera calculado pelo Pagseguro
	        $valor_envio = '0.00';

        	# grava dados na sessao do usuario
        	$session->set( 'dados_envio',
    		array(
    			'cod_servico' => $__cod_servico,
    			'forma_envio' => $_servicos_de_entrega[$__cod_servico],
    			'valor_envio' => $valor_envio ) );
    	endif;
	endif;
?>
<?php include 'frontend_header.php'; ?>

	<h2>Carrinho de Compras</h2>

	<section class="carrinho">
        <?php
            $i = 1;
            $total_items = $carrinho->total_items();
            if( $total_items > 0 ){ ?>

        <!-- Container: Carrinho de compras  -->
        <div style="overflow-x:auto;">
        	<form action="<?=$_SERVER['PHP_SELF'];?>" method="post">
	        	<table class="table-sysvendas-cart">
	        		<thead>
	        			<tr>
	              			<th>Descrição</th>
	              			<th>Qtd.</th>
	              			<th>Preço</th>
	              			<th>Sub-Total</th>
	              			<th>Excluir</th>
	        			</tr>
	        		</thead>

	        		<tbody>
		            	<?php foreach ($carrinho->contents() as $items): ?>
	                    <tr>
	                      	<td>
	                      		<input type="hidden" name="<?=$i;?>[rowid]" value="<?=$items['rowid'];?>">
	                      		<?=$items['name']; ?> - <?=$items['options']['modelo_title']; ?></td>
	                      	<td>
	                      		<input type="text" name="<?=$i.'[qty]';?>" id="qtd"
	                      		value="<?=$items['qty'];?>" maxlength="2" size="5"
	                      		onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                      		</td>
	                      	<td><?=$carrinho->format_number($items['price']); ?></td>
	                      	<td>R$<?=$carrinho->format_number($items['subtotal']);?></td>
	                      	<td>
	                      		<a class="remove_item" title="Excluir este item do carrinho de compras?"
	                      		href="?remover=<?=$items['rowid'];?>">remover</a>
                      		</td>
	                    </tr>
		            	<?php $i++; endforeach; ?>
					</tbody>

					<tfoot>
						<tr>
							<td>
								<input type="button" class="retcart" value="continuar comprando" onclick="javascript:window.location ='produtos.php';" /></td>
							<td>
								<input type="submit" class="upcart" name="" value="Atualizar Carrinho"></td>
							<td colspan="3">
								<b>Total dos produtos:</b> R$<strong>
								<?php
									echo number_format( $carrinho->total(), 2, ',', '.' );
								?>
								</strong></td>
						</tr>
					</tfoot>

	        	</table>
        	</form>
        </div><!-- // tabela carrinho -->

        <br>
        <hr>

        <div>
            <h3>Escolha uma Forma de Envio</h3>
            <p>
            	Escolha abaixo uma das formas de envio da mercadoria oferecido. 
            	Essa informação será enviada ao Pagseguro para o devido calculo de taxa de envio. 
            	Você também pode retirar sua compra diretamente em nossa loja.
            </p>
            <?php 
            	# verificando se ja setou a forma de envio
            	$_tmp = $session->dados_envio ;

            	foreach ($_servicos_de_entrega as $key => $value)
            		echo "<input type='radio' onclick=\"javascript:window.location='?forma_envio={$key}';\" 
            			name='forma_envio' value='{$value}' 
        				".((isset($_tmp)&&$_tmp['forma_envio']==$value)?'checked':'')."> {$value} <br>";
            ?>
	        <p>
	        	<strong>Nota:</strong> O valor de envio será Calculado por Pagseguro no ato do pagamento dos produtos.<br>
	        	<strong>Nota:</strong> Selecione "<strong>Não especificado</strong>" se quiser deicidir durante o pagamento no Pagseguro.<br>
	        </p>
        </div>

        <?php
            # seta o botao de continuar
            $__enabled = (isset( $_tmp )&&count($_tmp) > 0 ) ? 'enabled' : 'disabled' ;
        ?>

        <br><hr><br>

        <div class="div-fechar-pedido">
            <input type="button" value="Finalizar Compra" id="fechar_pedido" class="btn-fc-<?=$__enabled;?>"
            	onclick="javascript:window.location ='finalizar_compra.php';" <?=$__enabled;?> />
        </div>

        <br>

        <?php }else{ ?>
            
            Você ainda não adicionou produtos ao carrinho de compras.

        <?php } ?>

	</section>

<?php include 'frontend_footer.php'; ?>