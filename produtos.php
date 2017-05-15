<?php


	# BANCO DE DADOS DE PRODUTOS
	# --------------------------
	# Criando um array que guarda os Produtos
	# Isto é a simulação de um Banco de dados Hipotetico.
	# Na vida real, você deve fazer essa consulta no banco de dados
	# e obter os produtos a ser exibidos via MySQL, ORACLE, etc.
	require_once 'database/database.php';

	# @var Array - contem lista de produtos
	$_produtos = Database::get( 'produtos' );


	# VERIFICANDO SOLICITAÇÔES
	# ------------------------
	# Quando o usuario clica no botão adicionar produto ao carrinho,
	# essa página é encarregada de processar esse pedido.
	# Para isso, ela verifica se a URL atual contém um parametro
	# chamado "adicionar_ao_carrinho".
	# Esse parametro, guarda o ID do produto.
	# Você pode - e deve - implementar outro mecanismo de processo
	# que melhor se enquadre na necessidade do seu sistema.
	# No meu caso eu costumo fazer esses processos via jQuery/Ajax.
	if( isset($_GET['adicionar_ao_carrinho']) &&
		is_numeric($_GET['adicionar_ao_carrinho']) ):

		# armazenando id do produto para facilitar manipulação
		# aproveitamos para garantir que tenha apenas numeros.
		# @var int -
		$__pid = preg_replace( '/[^\d-]+/','', $_GET['adicionar_ao_carrinho'] ) ;

		# quantidade de produtos esta na URL tambem
		# @var int -
		$__qtd = preg_replace( '/[^\d-]+/','', $_GET['qtd'] ) or 1;

	    # obtendo as informacoes do produto da sua base de dados
	    # @var Array -
	    $_produto = isset($_produtos[$__pid]) ? $_produtos[$__pid] : null ;

	    # verificando se realmente o produto foi encontrado
	    if( $_produto != null ){

	        # gera o array que sera gravado no carrinho de compras
	        # @var Array -
	        $_data = array(
	            'id'      => $_produto['id'],
	            'qty'     => $__qtd,
	            'price'   => $_produto['preco'],
	            'name'    => $_produto['titulo'],
	            'options' => array(
	            	'modelo'       => $_produto['modelo'],
	            	'modelo_title' => $_produto['modelo_titulo'],
	            	'peso'         => $_produto['peso']
	            )
	        );

			# solicitamos a inclusao do Objeto carrinho,
			# ele nos permite gerenciar o carrinho de compras do cliente.
			require_once 'classes/LIBVENDAS/Cart.class.php' ;
			$carrinho = new LIBVENDAS\Cart ;

	        # a biblioteca carrinho, precisa de um parametro a mais
	        # para corrigir o problema om caracteres acentuados.
	        $carrinho->product_name_rules = '\d\D';

	        # adicionando o produto ao carrinho
	        $carrinho->insert( $_data );

	        # @var Array - guarda o resultado de um processamento 
	        # do sistema.
	        $_message = array( 'success' => 1, 
	        	'message' => 'Produto adicionado ao Carrinho de Compras' );
	    }
	    else{
	        $_message = array( 'success' => 0, 
	        	'message' => 'Esse produto não foi encontrado.' );
	    }

	endif;

?>

<?php 
	# Incluindo o cabecalho da pagina html
	include 'frontend_header.php'; ?>

	<h2>Produtos</h2>

	<?php
		# ALERTA DO SISTEMA
		# -------------------
		# Verificando se ha alguma mensagem sobre produto
		# adicionado ao carrinho de compras 
		if(isset($_message) && count($_message) > 0 )
			echo 
				"<div class='sysvendas-alertar-{$_message['success']}'>
					{$_message['message']} 
				</div>";
	?>

	<section class="lista-produtos">
		<?php foreach ($_produtos as $_produto): ?>

			<div class='produto'>
				<div class='produto-imagem'>
					<img src='<?=$_produto['img'];?>'>
				</div>
				<div class='produto-info'>
					<h2><?=$_produto['titulo'];?></h2>
					<p><?=$_produto['desc'];?></p>
					<span>R$<?=number_format($_produto['preco'], 2, ',', '.');?></span>
					<a href='?adicionar_ao_carrinho=<?=$_produto['id'];?>&qtd=1'
						class='btn-add-carrinho'>
						Adicionar ao Carrinho
					</a>
				</div>
			</div>

		<?php endforeach;?>
	</section>


<?php include 'frontend_footer.php'; ?>