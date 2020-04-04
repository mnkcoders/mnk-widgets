<?php namespace CODERS\Widgets;
/*******************************************************************************
 * Plugin Name: MNK Widgets
 * Plugin URI: https://coderstheme.org
 * Description: Faster Widget Developement
 * Version: 1.0.0
 * Author: Coder#1
 * Author URI: 
 * License: GPLv2 or later
 * Text Domain: coders_widgets
 * Class: \CODERS\Widgets\WidgetBase
 * 
 * @author Jaume Llopis
 ******************************************************************************/
defined('ABSPATH') or die;

/**
 * Widget base para plantillas de widget
 * @version 1.0.1
 * Agregada funcionalidad para registrar scripts y css en admin y frontend fácilmente
 * 
 * utilizar métodos registerAdmin*^y registerWidget*
 * 
 */
abstract class WidgetBase extends \WP_Widget {

    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_MEDIA = 'media';
    const TYPE_MEDIA_LIST = 'media-list';
    const TYPE_NUMBER = 'number';
    const TYPE_FLOAT = 'float';
    const TYPE_PASSWORD = 'password';
    const TYPE_DATE = 'date';
    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_SELECT = 'select';
    const TYPE_MULTI_SELECT = 'multi-select';

    const ASSET_TYPE_ADMIN = 'admin';
    const ASSET_TYPE_WIDGET = 'widget';
    
    const INPUT_ARRAY_SEPARATOR = '|';

    /**
     * @var array Scripts del widget
     */
    private $_scripts = array(
        self::ASSET_TYPE_WIDGET => array(),
        self::ASSET_TYPE_ADMIN => array(),
    );
    /**
     * @var array Estilos del widget
     */
    private $_styles = array(
        self::ASSET_TYPE_WIDGET => array(),
        self::ASSET_TYPE_ADMIN => array(),
    );
    /**
     * @var array Parámetros del widget
     */
    private $_inputs = array();

    /**
     * Auto-constructor
     */
    function __construct( ) {
        
        $this->registerWidgetInputs();
        
        parent::__construct( 
                $this->defineWidgetId(),
                $this->defineWidgetTitle(),
                $this->defineWidgetOptions() );
        //esto permite tener un control mas generalizado sobre las css del widget
        //truñopress agrega el prefijo widget_ delante, destrozando un poco el acceso en segun que contextos
        $this->widget_options['classname'] = $this->defineWidgetClass();

        //registrar estilos y scripts automáticamente
        if( is_admin() ){
            $this->initAdminAssets();
        }
        else{
            $this->initWidgetAssets();
        }
    }
    /**
     * @param string $name
     * @param array $arguments
     * @return boolean| mixed
     */
    public function __call($name, $arguments) {
        
        $callback = sprintf('run%sCallback',$name);
        
        return (method_exists($this, $callback)) ?
                $this->$callback( $arguments ) :
                FALSE;
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        
        return $name;
    }
    /**
     * <custom />
     * @param string $TAG
     * @param array $attributes
     * @param mixed $content
     * @return HTML
     */
    protected static final function __HTML( $TAG , array $attributes , $content = null ){

        if( isset( $attributes['class'])){
            if(is_array($attributes['class'])){
                $attributes['class'] = implode(' ', $attributes['class']);
            }
        }
        
        $serialized = array();
        
        foreach( $attributes as $var => $val ){
            $serialized[] = sprintf('%s="%s"',$var,$val);
        }
        
        if( !is_null($content) ){

            if(is_object($content)){
                $content = strval($content);
            }
            elseif(is_array($content)){
                $content = implode(' ', $content);
            }
            
            return sprintf('<%s %s>%s</%s>' , $TAG ,
                    implode(' ', $serialized) , strval( $content ) ,
                    $TAG);
        }
        
        return sprintf('<%s %s />' , $TAG , implode(' ', $serialized ) );
    }
    /**
     * ID del widget generado para el constructor
     * @return string
     */
    protected static final function defineWidgetId(){
        
        return sprintf('coders-%s-widget' ,self::getWidgetId() );
    }
    /**
     * Sobrecargar con la clase del widget
     * @return string
     */
    protected static function defineWidgetClass(){
        
        return preg_replace('/_/', '-', self::defineWidgetId());
    }
    /**
     * Sobrescribir con el título del widget
     * @return string
     */
    public static function defineWidgetTitle(){
        
        return get_class( );
    }
    /**
     * Sobrecargar con la descripción del widget
     * @return string
     */
    public static function defineWidgetDescription(){
        
        return __( 'Un widget de CODERS' ,'coders_widget_pack');
    }
    /**
     * Sobrecargar con las opciones del widget
     * @return array
     */
    protected function defineWidgetOptions(){

        return array( 'description' => $this->defineWidgetDescription( ) );
    }
    /**
     * Inicializa los parámetros del widget
     * @return \CODERS\WidgetBase
     */
    protected function registerWidgetInputs(){

        return $this->inputRegister( 'title',
                self::TYPE_TEXT, '',
                __('T&iacute;tulo','coders_widget_pack'));
    }
    /**
     * Inicializa los assets del form de administración del widget
     */
    private final function initAdminAssets() {

        $styles = $this->_styles[self::ASSET_TYPE_ADMIN];

        $scripts = $this->_scripts[self::ASSET_TYPE_ADMIN];

        add_action('admin_enqueue_scripts', function() use($styles, $scripts) {
            foreach( $styles as $style => $deps ){
                switch( $style ){
                    case 'media-gallery':
                        wp_enqueue_style(
                            'coders-media-selector-style',
                            $this->getAssetUrl('media-gallery.css' , false ));
                        break;
                    default:
                        wp_enqueue_style(
                            sprintf('coders-widget-%s-style',$style),
                            $this->getAssetUrl($style.'.css'),
                            $deps);
                        break;
                }
            }
            foreach( $scripts as $script => $deps ){
                switch ( $script ){
                    case 'media-gallery':
                        //incluir las librerías de WP para el mediamanager
                        wp_enqueue_media();
                        wp_enqueue_script(
                            'coders-media-selector-script',
                            $this->getAssetUrl( 'media-gallery.js' , false ),
                            $deps);
                        break;
                    default:
                        wp_enqueue_script(
                            sprintf('coders-widget-%s-script',$script),
                            $this->getAssetUrl($script.'.js'),
                            $deps);
                        break;
                }
            }
        });
    }
    /**
     * Registra los assets del widget publico
     */
    private final function initWidgetAssets() {
        
        $styles = $this->_styles[self::ASSET_TYPE_WIDGET];

        $scripts = $this->_scripts[self::ASSET_TYPE_WIDGET];

        add_action('wp_enqueue_scripts', function() use($styles, $scripts) {

            foreach( $scripts as $script => $deps ){
                wp_enqueue_script(
                        sprintf('coders-widget-%s-script',$script),
                        $this->getAssetUrl($script.'.js'),
                        $deps);
            }
            
            foreach( $styles as $style => $deps ){
                wp_enqueue_style(
                        sprintf('coders-widget-%s-style',$style),
                        $this->getAssetUrl($style.'.css'),
                        $deps);
            }
        });
    }
    /**
     * Registra un script de cliente
     * @param string $script
     * @param array $deps
     * @return \CodersWidgetBase
     */
    protected final function registerWidgetScript( $script = 'widget' , $deps = array() ){
        if( !isset($this->_scripts[ self::ASSET_TYPE_WIDGET ][ $script ] ) ){
            $this->_scripts[self::ASSET_TYPE_WIDGET][$script] = $deps;
        }
        return $this;
    }
    /**
     * Registra un estilo de cliente
     * @param string $style
     * @param array $deps
     * @return \CodersWidgetBase
     */
    protected final function registerWidgetStyle( $style = 'widget' , $deps = array() ){
        if( !isset($this->_styles[ self::ASSET_TYPE_WIDGET ][ $style ] ) ){
            $this->_styles[self::ASSET_TYPE_WIDGET][$style] = $deps;
        }
        return $this;
    }
    /**
     * Registra un script de administrador
     * @param string $script
     * @param array $deps
     * @return \CodersWidgetBase
     */
    protected final function registerAdminScript( $script = 'admin' , $deps = array() ){
        if( !isset($this->_scripts[ self::ASSET_TYPE_ADMIN ][ $script ] ) ){
            $this->_scripts[self::ASSET_TYPE_ADMIN][$script] = $deps;
        }
        return $this;
    }
    /**
     * Registra un estilo de administrador
     * @param string $style
     * @param array $deps
     * @return \CodersWidgetBase
     */
    protected final function registerAdminStyle( $style = 'admin' , $deps = array() ){
        if( !isset($this->_styles[ self::ASSET_TYPE_ADMIN ][ $style ] ) ){
            $this->_styles[self::ASSET_TYPE_ADMIN][$style] = $deps;
        }
        return $this;
    }
    /**
     * @version 1.2 Requiere del uso de la clase ReflectionClass para conocer
     * la clase invocante y procedente
     * @return string
     */
    protected static final function getWidgetId( ){
        
        $called = new \ReflectionClass(get_called_class());
        $full_path = strtolower( $called->getFileName() );
    
        //normalizar (si está en local windows
        $file = explode('/', preg_replace('/\\\\/', '/', $full_path ) );
        $output = $file[ count($file) - 1 ];
        return substr($output, 0 , strrpos($output, '.' ) );
    }
    /**
     * Lista las opciones de un input de contenido
     * Un proveedor de listado de  opciones puede ser getPostOptions()
     * @param string $name
     * @return array
     */
    protected final function getOptions( $name ){
        if(array_key_exists($name, $this->_inputs)){
            $format_parts = explode('_', $name);
            $callback = 'get';
            foreach( $format_parts as $string ){
                $callback .= strtoupper(substr($string, 0,1))
                        . strtolower(substr($string, 1, strlen($string)-1));
            }
            $callback .= 'Options';
            //$callback = sprintf('get%sOptions', preg_replace('/_/','',strtolower( $name ) ));
            return method_exists($this, $callback) ? $this->$callback() : array();
        }
    }
    /**
     * Cargar vista del widget, si exsite una vista personalizada, la selecciona, sino, recupera la original
     * Por defecto en la carpeta del widget, pero buscará primero si existe alguna vista en el tema
     * @param string $view
     * @return string
     */
    protected final function getView( $view = 'default' ){
        
        $theme_path = sprintf( '%s/widgets/%s/%s.php',
                get_stylesheet_directory(),
                self::getWidgetId( ) , $view );

        return file_exists( $theme_path ) ?
                //busca una plantilla personalizada en el tema
                $theme_path :
                //busca una plantilla en la carpeta del widget
                WidgetManager::widgetView(self::getWidgetId(), $view);
    }
    /**
     * @param string $asset
     * @return string
     */
    protected static final function basePath( $asset ){
        return sprintf('%s/widgets/%s/%s',
                __DIR__,
                self::getWidgetId(), $asset );
    }
    /**
     * @param string $asset
     * @return string
     */
    protected static final function themePath( $asset ){
        return sprintf('%s/widgets/%s/%s',
                get_stylesheet_directory(),
                self::getWidgetId() , $asset );
    }
    /**
     * @param string $asset
     * @return string
     */
    protected static final function baseUrl( $asset ){
        return sprintf('%s/widgets/%s/%s',
                plugin_dir_url(__FILE__),
                self::getWidgetId() , $asset );
    }
    /**
     * @param string $asset
     * @return string
     */
    protected static final function themeUrl( $asset ){
        return sprintf('%s/widgets/%s/%s',
                get_stylesheet_directory_uri(),
                self::getWidgetId() , $asset );
    }
    /**
     * Ruta del asset solicitado, estilo, script, ...
     * @param string $asset
     * @return string
     */
    protected final function getAsset( $asset ){

        $theme_path = self::themePath($asset);
        
        $base_path = self::basePath($asset);
        
        return file_exists($theme_path) ?
                //busca un recurso personalizado en el tema
                $theme_path :
                //busca un recurso personalizado en la carpeta del widget
                $base_path;
    }
    /**
     * URL de estilos y scripts del widget, estilo, script, ...
     * @param string $asset
     * @return string
     */
    protected final function getAssetUrl( $asset ){
                
        $theme_path = self::themePath($asset);
        
        $theme_url = self::themeUrl($asset);
        
        $base_url = self::baseUrl($asset);
        
        return file_exists($theme_path) ? $theme_url : $base_url;
    }
    /**
     * Lista los archivos de tipo en el directorio de personalizaciones del tema
     * @param string $file_type
     * @return array
     */
    protected final function listThemeDir( $file_type = 'php' ){

        $output = array();
        
        $theme_dir = sprintf('%s/widgets/%s/',
                get_stylesheet_directory(),
                self::getWidgetId( ) );
        
        if ( strlen($file_type) > 0 && file_exists($theme_dir) && $handle = opendir($theme_dir)) {
            
            while (false !== ($file = readdir($handle)))
            {
                if( ( $offset = strrpos($file, '.' . $file_type ) ) !== false ){
                    $file_name = substr($file, 0 , $offset );
                    $output[ $file_name ] = sprintf('%s ( %s )',
                            $file_name ,
                            __('Vista personalizada','coders_widget_pack'));
                }
            }
            closedir($handle);
        }
        
        return $output;
    }
    /**
     * Lista los archivos de tipo en el directorio de personalizaciones del tema
     * @param string $file_type
     * @return array
     */
    protected final function listViewDir( ){

        $output = array();
        
        $html_dir = $this->getAsset(sprintf('widgets/%s/html',self::getWidgetId()));

        if ( file_exists($html_dir) && $handle = opendir($html_dir)) {
            
            while (false !== ($file = readdir($handle)))
            {
                if( ( $offset = strrpos($file, '.php' ) ) !== false ){
                    $file_name = substr($file, 0 , $offset );
                    $output[ $file_name ] = $file_name;
                }
            }
            closedir($handle);
        }
        
        return $output;
    }
    /**
     * Registra un input en el  widget. Definir en el constructor
     * @param string $input nombre único del input
     * @param string $type Tipo de input
     * @param mixed $value
     * @param string $label Etiqueta a mostrar
     * @param string $description Descripción que aparecerá bajo el campo en el formulario del widget
     * @param array $options Lista de opciones adicionales si el input requiere de alguna propiedad extra
     * @return WidgetBase
     */
    protected final function inputRegister( $input , $type = self::TYPE_TEXT , $value = '' , $label = '', $description = '', array $options = null ){
     
        if( !array_key_exists($input, $this->_inputs)){
            $this->_inputs[ $input ] = array(
                'name' => $input,
                'type' => $type,
                'value' => $value,
                'label' => $label,
                'description' => $description,
            );
            
            if( !is_null($options) && count($options)){
                $this->_inputs[$input]['config'] = $options;
            }
            
            if( $type === self::TYPE_MEDIA || $type === self::TYPE_MEDIA_LIST ){
                //si es un input multimedia, incluir directamente las scripts y estilos requeridos
                $this->registerAdminScript(
                        'media-gallery',
                        array('jquery'))
                        ->registerAdminStyle('media-gallery');
            }
        }
        return $this;
    }
    /**
     * @return array Lista los campos del widget
     */
    protected final function inputFields(){
        return array_keys($this->_inputs);
    }
    /**
     * @param string $input Nombre único
     * @param string $type Tipo de input
     * @param mixed $value Valor del input
     * @param array $meta Configuración adicional del input
     */
    protected function inputDisplay( $input , $type , $value , array $meta = array() ){
        switch( $type ){
            case self::TYPE_CHECKBOX:
                printf('<input type="checkbox" name="%s" id="%s" value="1" %s style="float:right;" />',
                        $this->get_field_name($input),
                        $this->get_field_id($input),
                        intval( $value ) > 0 ? 'checked' : '');
                break;
            case self::TYPE_MEDIA: //solo un spinner para acceder al ID de adjunto, extender en resto de widgets si interesa
                //dejar que el script adjunto inicialize el contenido del input
                $att_url = $value > 0 ? wp_get_attachment_url( $value ) : false;
                $image = $att_url !== false ? sprintf('<img src="%s" alt="%s" />',
                        $att_url , get_the_title($value)) :
                        '<span class="empty"><!-- media-not-found --></span>';
                printf( '<button class="media-selector widefat %s" id="%s" name="%s" value="%s">%s</button>',
                        intval($value) > 0 ? 'selected' : '',
                        $this->get_field_id($input),
                        $this->get_field_name($input),
                        $value , $image );
                printf('<script type="text/javascript">new codersMediaController("%s","%s")</script>',
                        $this->get_field_id($input),
                        $this->get_field_name($input));
                break;
            case self::TYPE_MEDIA_LIST:
                //dejar que el script adjunto inicialize el contenido del input
                printf( '<ul class="media-selector multiple" id="%s" name="%s">',
                        $this->get_field_id($input),
                        $this->get_field_name($input));
                if( !is_array($value) ){
                    //convertir en array y luego comprobar que el primer item contiene un valor
                    printf('<!--%s-->',$value);
                    $value = explode(self::INPUT_ARRAY_SEPARATOR, $value);
                }
                if(strlen($value[0])){
                    //luego iterear para renderizar la galería
                    foreach( $value as $item ){
                        printf('<li class="media-item">%s%s</li>',
                                sprintf('<input type="hidden" name="%s[]" value="%s" />',
                                        $this->get_field_name($input),$item),
                                sprintf('<img src="%s" alt="%s" />',
                                        wp_get_attachment_url($item),get_the_title($item)));
                    }
                }
                printf('</ul><script type="text/javascript">new codersMediaController("%s","%s")</script>',
                        $this->get_field_id($input),
                        $this->get_field_name($input));
                break;
            case self::TYPE_FLOAT:
            case self::TYPE_NUMBER:
                printf('<input type="number" name="%s" id="%s" value="%s" step="%s" min="%s" %s class="widefat" />',
                        $this->get_field_name($input),
                        $this->get_field_id($input),
                        $value,
                        isset($meta['step']) ? $meta['step'] : 1,
                        isset($meta['min']) ? $meta['min'] : 0,
                        isset($meta['max']) ? sprintf('max="%s"',$meta['max']) : '');
                break;
            case self::TYPE_SELECT:
                if( count( $option_list = $this->getOptions($input) ) ){
                    printf('<select name="%s" id="%s" class="widefat alignright">',
                            $this->get_field_name($input),
                            $this->get_field_id($input));
                    foreach ( $option_list as $option => $label) {
                        printf('<option value="%s" %s>%s</option>',
                                $option,
                                $value == $option ? 'selected' : '',
                                $label);
                    }
                    print '</select>';
                }
                else{
                    printf('<i class="widefat alignright" style="float: right;">%s</i>',$value);
                }
                break;
            case self::TYPE_PASSWORD:
                printf('<input type="password" name="%s" id="%s" value="%s" class="widefat" %s />',
                        $this->get_field_name($input),
                        $this->get_field_id($input),
                        $value,
                        isset($meta['placeholder']) ? sprintf('placeholder="%s"',$meta['placeholder']) : '');
                break;
            case self::TYPE_DATE:
                printf('<input type="date" name="%s" id="%s" value="%s" class="widefat" />',
                        $this->get_field_name($input),
                        $this->get_field_id($input),
                        $value);
                break;
            case self::TYPE_TEXTAREA:
                print self::__HTML('textarea', array(
                    'name' => $this->get_field_name($input),
                    'id' => $this->get_field_id($input),
                    'class' => 'widefat',
                    'placeholder' => isset($meta['placeholder']) ? $meta['placeholder'] : '',
                ), $value);
                break;
            case self::TYPE_TEXT:
            default:
                print self::__HTML( 'input', array(
                        'type'=>'text',
                        'name'=>$this->get_field_name($input),
                        'id' => $this->get_field_id($input),
                        $value,
                        'placeholder' => isset($meta['placeholder']) ? sprintf('placeholder="%s"',$meta['placeholder']) : ''
                    ));
                break;
        }
    }
    /**
     * Valida la entrada de datos
     * @param string $type
     * @param string $value
     * @param array $validators
     * @return mixed
     */
    protected function inputValidate( $type , $value , $validators = array() ){
        switch( $type ){
            case self::TYPE_MEDIA:
            case self::TYPE_NUMBER:
            case self::TYPE_CHECKBOX:
                return intval( $value );
            case self::TYPE_FLOAT:
                return floatval( $value );
            case self::TYPE_MEDIA_LIST:
                if (is_array($value)) {
                    //serializa por defecto
                    return count($value) > 0 ? implode( self::INPUT_ARRAY_SEPARATOR, $value) : '';
                }
                return $value;
            case self::TYPE_TEXT:
            case self::TYPE_TEXTAREA:
                //elimina los tags si se requiere
                $value = isset($validators['striptags']) && $validators['striptags'] ?
                    strip_tags( trim( $value ) ) :
                    trim($value);

                $size = isset($validators['size']) ? intval($validators['size']) : 0;
                //recorta la cadena si se requiere
                if( $size && $size < strlen($value) ){
                    $value = substr($value, 0,$size);
                }
                return trim( $value );
            default:
                return $value;
        }
    }
    /**
     * @param array $instance Valores de entrada
     * @return array Valores de salida, formateados y por defecto si no se definen en la entrada
     */
    protected function inputImport( array $instance , array $old = null ){
        $output = array();
        foreach( $this->_inputs as $input => $meta ){
            $output[ $input ] = $this->inputValidate(
                    $meta['type'],
                    isset($instance[$input]) ? $instance[$input] : $meta['value'],
                    isset($meta['config']) ? $meta['config'] : array());
        }
        return $output;
    }
    /**
     * Extrae los valores de un input serializado para el widget
     * @param array $instance
     * @param array $old
     * @return array
     */
    protected function inputExtract( array $instance , array $old = null ){
        $output = array();
        foreach( $this->_inputs as $input => $meta ){
            if( isset( $instance[$input])){
                switch( $meta['type']){
                    case self::TYPE_MEDIA_LIST:
                        $output[$input] = explode(self::INPUT_ARRAY_SEPARATOR, $instance[$input]);
                        break;
                    default:
                        $output[$input] = $instance[$input];
                        break;
                }
            }
            else{
                $output[$input] = $meta['value'];
            }
        }
        return $output;
    }
    /**
     * form de administrador
     * @param type $instance
     */
    function form($instance) {
        foreach( $this->_inputs as $input => $meta ){
            printf('<p><label for="%s" class="caption">%s</label>',$this->get_field_id($input),$meta['label']);
            $this->inputDisplay( $input, $meta['type'],
                    isset( $instance[ $input ] ) ? $instance[$input] : $meta['value'],
                    isset($meta['config']) ? $meta['config'] : array() );
            if(strlen($meta['description'])){
                printf('<small class="widefat clearfix info">%s</small>',$meta['description']);
            }
            print '</p>';
        }
        
        if(count($this->_scripts[self::ASSET_TYPE_ADMIN])){
            printf('<p><small>%s</small><p>',__('Recuerda recargar la p&aacute;gina para configurar este widget.','coders_widget_pack')); 
        }
        
        print self::__HTML('p', array('class'=>'widefat clearfix') , '<hr/>' );
    }
    /**
     * actualización del widget (form de administrador)
     * @param type $new_instance
     * @param type $old_instance
     */
    function update($new_instance, $old_instance) {
        return $this->inputImport($new_instance, $old_instance );
    }
    /**
     * presentación frontal
     * @param array $args
     * @param array $instance
     */
    function widget($args, $instance){
        
        print $args['before_widget'];
        
        $this->display( $this->inputImport( $instance ) , $args );
        
        print $args['after_widget'];
    }
    /**
     * @param array $instance Datos dela instancia para mostrar el widget
     * @param array $args contiene las definiciones del encabezado y opciones del widget, se heredan 
     */
    abstract protected function display( $instance , $args = null );
}

/**
 * 
 */
final class Manager{
    
    const WIDGET_LIST_OPTION = 'coders_widget_pack_widgets';
    
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_INVALID = 'disabled';
    
    private static $_INSTANCE = null;
    
    private $_updated = FALSE;
    
    private $_widgets = array();
    
    private final function __construct() {
        
        //register action 
        $this->preload()->setupAdmin();
        //add_action( 'init' , array( $this , 'preload' ) );
    }
    /**
     * @return string
     */
    private static final function pluginPath(){
    
        return preg_replace('/\\\\/', '/', __DIR__);
    }
    /**
     * @return String|URL
     */
    public static final function pluginUrl(){
        
        return plugin_dir_url(__FILE__);
    }
    /**
     * @param string $widget
     * @return string
     */
    private static final function widgetClass( $widget ){

        $classParts = explode('-', $widget);

        $class = '';

        foreach( $classParts as $chunk ){

            $class .= strtoupper( substr( $chunk , 0 , 1 ) )
                    . strtolower(substr($chunk, 1, strlen($chunk)-1));
        }
        
        return sprintf('Coders%sWidget', $class );
    }
    /**
     * Comprueba que existe un widget
     * @param string $widget
     * @return boolean
     */
    public static final function checkWidget( $widget ){
        
        return file_exists( sprintf( '%s/%s' , self::pluginPath( ) , $widget ) );
    }
    /**
     * @return array
     */
    private final function importWidgetDir(){

        $path = sprintf('%s/widgets/',self::pluginPath());
        //var_dump($path);
        $output = array();
        if ( file_exists($path) && $handle = opendir( $path ) ) {
                while ( FALSE !== ( $widget = readdir($handle))){
                    switch( $widget ){
                        case '.':
                        case '..':
                            break;
                        default:
                            $output[] = $widget;
                            break;
                    }
                }
        }
        
        return $output;
    }
    /**
     * @return array
     */
    private final function importSetup(){

        $import = get_option( self::WIDGET_LIST_OPTION , '' );
        
        if(strlen($import)){            
            $extract = base64_decode($import);
            $decode = json_decode($extract,TRUE);
            //var_dump($decode);
            return is_array($decode) ? $decode : array();
        }
        
        return array();
    }
    /**
     * @param string $widget
     * @return string
     */
    public final function status( $widget){
        if( array_key_exists($widget, $this->_widgets) ){
            return $this->_widgets[$widget] ?
                    self::STATUS_ACTIVE :
                    self::STATUS_INACTIVE;
        }
        return self::STATUS_INVALID;
    }
    /**
     * @param string $widget
     * @param boolean $status
     * @return \CODERS\Widgets\Manager
     */
    public final function toggle( $widget ){
        
        if(array_key_exists($widget, $this->_widgets)){

            $this->_widgets[$widget] = !$this->_widgets[ $widget ];

            $this->_updated = TRUE;
        }
        return $this;
    }
    /**
     * @return \CODERS\Widgets\Manager
     */
    public final function exportSetup(){
        if( $this->_updated){
            //exporta los widgets activos
            $export = json_encode($this->widgets());
            //guarda la lista serializada en options
            update_option( self::WIDGET_LIST_OPTION , base64_encode($export));
            $this->_updated = false;
        }
        return $this;
    }
    /**
     * @return array
     */
    public final function listWidgets(){
        
        $widgets = array();
        
        foreach( $this->widgets(false) as $widget_id => $active ){
            $class = self::widgetClass($widget_id);
            $name = class_exists($class) ? $class::defineWidgetTitle() : $class;
            $widgets[ $widget_id ] = array(
                'name' => $name,
                'status' => $active ? self::STATUS_ACTIVE : self::STATUS_INACTIVE
            );
        }
        
        return $widgets;
    }
    /**
     * @param boolean $activeOnly
     * @return array
     */
    public final function widgets( $activeOnly = true ){
        if( $activeOnly ){
            $output = array();
            foreach( $this->_widgets as $widget => $status){
                if( $status ){
                    $output[] = $widget;
                }
            }
            return $output;
        }
        
        return $this->_widgets;
    }
    /**
     * @param string $widget
     * @param boolean $register
     * @return boolean
     */
    private final function importWidget( $widget , $register = FALSE ){

        $class = self::widgetClass($widget);
        
        $path = sprintf('%s/widgets/%s/%s.php',self::pluginPath(),$widget,$widget);
        //var_dump($class);
        //var_dump($path);
        if(strlen($class) && file_exists($path)){
            
            require_once $path;

            if( class_exists($class) && is_subclass_of($class, WidgetBase::class ) ){

                if( $register ){

                    add_action( 'widgets_init', function() use( $class ){

                        register_widget( $class );
                    } );
                }
                
                return TRUE;
            }
        }

        return FALSE;
    }
    /**
     * @return \CODERS\Widgets\Manager
     */
    private final function setupAdmin(){
        if(is_admin()){
            add_action('admin_menu',function(){
                /*add_menu_page(
                        __('Coder Widgets', 'coders_widgets' ),
                        __('Coder Widgets', 'coders_widgets' ),
                        'administrator', 'coder-widgets',
                        function(){
                            //var_dump(\CODERS\Widgets\Manager::instance()->widgets(FALSE));
                            printf('<h2>%s</h2><div id="coder-widget-manager"></div>',
                                    __('Coder Widget Manager','coders_widgets'));
                        },
                        'dashicons-welcome-widgets-menus', 50 );*/
                add_submenu_page('themes.php',
                        __('Coder Widgets', 'coders_widgets' ),
                        __('Coder Widgets', 'coders_widgets' ),
                        'administrator', 'coder-widgets',
                        function(){
                            //var_dump(\CODERS\Widgets\Manager::instance()->widgets(FALSE));
                            printf('<h2>%s</h2><div id="coder-widget-manager"></div>',
                                    __('Coder Widget Manager','coders_widgets'));
                        }, 100 );
            });

            add_action( 'admin_enqueue_scripts', function(){
                $url = \CODERS\Widgets\Manager::pluginUrl();
                wp_enqueue_script( 'mnk-widgets-script', sprintf('%s/widget-manager.js',$url), array('jquery'), '1.0.0' );
                wp_enqueue_style( 'mnk-widgets-css', sprintf('%s/widget-manager.css',$url), false, '1.0.0' );
            } );

            add_action( 'wp_ajax_coder_widget_list',  function(){
                $widgets = \CODERS\Widgets\Manager::instance()->listWidgets( );
                print json_encode($widgets);
                wp_die();
            } );

            add_action( 'wp_ajax_coder_widget_toggle',  function(){
                $request = filter_input_array(INPUT_POST);
                header('Content-Type: application/json');
                if( !is_null($request) && isset( $request[ 'widget' ] ) ){
                    $status = \CODERS\Widgets\Manager::instance( )
                            ->toggle( $request['widget'] ) //change
                            ->exportSetup()                 //save
                            ->status( $request['widget'] ); // extract
                    print json_encode(array(
                        'widget' => $request['widget'],
                        'status' => $status,
                        //'list' => \CODERS\Widgets\Manager::instance()->widgets(FALSE)
                    ) );
                }
                else{
                    print json_encode($request);
                }
                wp_die();
            } );
        }
        return $this;
    }
    /**
     * @return \CODERS\Widgets\Manager
     */
    private final function preload(){

        if(count($this->_widgets) === 0 ){
            
            $active_list = $this->importSetup();

            $widget_list = $this->importWidgetDir();
            
            foreach( $widget_list as $widget ){
                $active = in_array($widget, $active_list);
                if( $this->importWidget($widget, $active ) ){
                    $this->_widgets[$widget] = $active;
                }
            }
        }
        return $this;
    }
    /**
     * @return \CODERS\Widgets\Manager
     */
    public static final function instance(){
        
        if(is_null(self::$_INSTANCE)){

            self::$_INSTANCE = new Manager();
        }
        
        return self::$_INSTANCE;
    }
}

Manager::instance();
//WidgetBase::preload();


