<?php

namespace LIBVENDAS;

class Session
{
    private static $instance;
    private $session_expire_time = null ;

    /**
     * Metodo construtor
     *
     * @access public
     * @param int - tempo maximo para expiracao da sessao
     */
    public function __construct( $expire_time = null )
    {
        # Singleton - evita ser instanciado duas vezes
        self::$instance =& $this;

        $this->setExpiretime( $expire_time );

        session_start();
        header("Cache-control: private");

        # troca o ID da sessao a cada refresh
        # quando fecha browser destroi sessao
        # impede roubo de sessao
        session_regenerate_id();

        # setando arquivo ini( evita JS acessar sessao )
        ini_set( 'session.cookie_httponly' , true );
        ini_set( 'session.use_only_cookies', true );

        # verificando se sessao esta configurada para expirar apos inatividade
        if( ! is_null( $this->session_expire_time ) ):
            # verificando se sessao nao expirou por tempo
            if( isset($_SESSION['SS_ULTIMA_ATIVIDADE']) &&
                (time() - $_SESSION['SS_ULTIMA_ATIVIDADE'] >
                $this->session_expire_time ) ):
                # Sessao expirada: destroy sessao
                $this->destroy();
            endif;
        endif;
        # setando ultima atividade no sistema
        $_SESSION['SS_ULTIMA_ATIVIDADE'] = time();
    }

    public static function getInstance( $expire_time = null ){
        if(!isset(self::$instance))
            self::$instance = new self( $expire_time );
        return self::$instance;
    }
	
    public function setExpiretime($__value = null ){
        if($__value != null)
            $this->session_expire_time = $__value;
    }

    public function getExpiretime(){
        return $this->session_expire_time ;
    }

    public function __set( $name, $value ){
        $this->set($name,$value);
    }

    public function __get($name){
        return $this->get($name);
    }

    public function set($__name, $__value){
        $_SESSION[trim($__name)] = $__value;
    }

    public function get($__name = null){
        if($__name == null) return null;
        if(isset($_SESSION[trim($__name)]))
            return $_SESSION[trim($__name)];
        else
            return null;
    }

    public function del($name){
        unset($_SESSION[trim($name)]);
    }

    function destroy(){
        $_SESSION = array();
        session_destroy();
        session_regenerate_id();
    }
}