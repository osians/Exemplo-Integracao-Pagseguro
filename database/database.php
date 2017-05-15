<?php

	/**
	 * Classe Database
	 * ---------------
	 * 
	 * Esta é uma implementação simples de um Banco de dados em
	 * Arquivos de TEXTO. Usado apenas para fins didaticos
	 * para acompanhar o artigo sobre uso da API do PAGSEGURO.
	 * Recomendo o uso dessa classe apenas para testes,
	 * em produção o ideal é a utilização de um SGBD como
	 * MySQL, SQL Server, ORACLE, etc.
	 *
	 * --------------------------
	 * 
	 * @created 2017.04.22 18:44h
	 * @autor wanderlei santana <sans.pds@gmail.com>
	 */
	class Database
	{
		private static $ext = "" ;

		/**
		 * Retornar o caminho completo para uma tabela no
		 * servidor Web.
		 *
		 * @access private
		 * @param  string $__tablename - nome da Tabela
		 * @return string
		 */
		private static function dbfilepath( $__tablename = "tabela_a_carregar" ){
			return dirname(__FILE__) . DIRECTORY_SEPARATOR . strtolower( $__tablename . Database::$ext );
		}

		/**
		 * Dado o nome de uma tabela JSON, retorna as informações 
		 * contidas nessa tabela na forma de Array.
		 *
		 * @access public
		 * @param  string $__tablename - nome da tabela
		 * @return Array
		 */
		public static function get( $__tablename ){
			$__filepath = self::dbfilepath( $__tablename );
			if(!file_exists($__filepath))
				throw new Exception("Arquivo '$__filepath' não existe.", 1);
			return json_decode(file_get_contents($__filepath ), true );
		}

		public static function save( $__tablename, $_dados = array() ){
			$_tabela = Database::get( $__tablename ) ;
			if($_tabela == null) $_tabela = array();

			# verificando pela existencia de uma ID
			# Se existir sera a Key para o Array
			if(isset($_dados['id'])){
				$index = $_dados['id'];
			}else{
				$index = null;
				$tmp = count($_tabela) + 1;
				while($index == null){
					 $index = (!isset($_tabela[$tmp])) ? $tmp : null;
					 $tmp++;}}

			$_tabela[$index] = $_dados ;
			file_put_contents( self::dbfilepath( $__tablename ), json_encode($_tabela) );
			return $index;
		}

		public static function update( $__tablename, $__key = 0, $_dados = array() ){
			$_tabela = Database::get( $__tablename ) ;
			if($_tabela == null) return false;
			$_registro = (isset($_tabela[$__key]) && is_array($_tabela[$__key])) ? $_tabela[$__key] : null;
			if($_registro == null) return false;
			foreach ($_dados as $key => $value) $_registro[$key] = $value;
			$_tabela[$__key] = $_registro ;
			return file_put_contents( self::dbfilepath( $__tablename ), json_encode($_tabela) );
		}

		public static function remove( $__tablename, $__key = 0){
			$_tabela = Database::get( $__tablename ) ;
			unset($_tabela[$__key]);
			return file_put_contents( self::dbfilepath( $__tablename ), json_encode($_tabela) );
		}
	}
