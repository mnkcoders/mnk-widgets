<?php
/*******************************************************************************
 * Plugin Name: Coders Widget Pack
 * Plugin URI: https://mnkcoders.com
 * Description: Easy Widget Deploy
 * Version: 1.0.0
 * Author: Coder#1
 * Author URI: 
 * License: GPLv2 or later
 * Text Domain: coders_widget_pack
 * Class: CodersWidgetBase
 * 
 * @author Coder#1
 ******************************************************************************/
abstract class CodersWidgetBase extends \WP_Widget {

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
            add_action('admin_enqueue_scripts', array( $this , 'initAdminAssets' ) );
        }
        else{
            add_action('wp_enqueue_scripts', array( $this , 'initWidgetAssets' ) );
        }
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
        
        return __( 'A new Coders Widget' ,'coders_widget_pack');
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
     * @return array
     */
    private final function getAdminStyles(){
        return $this->_styles[self::ASSET_TYPE_ADMIN];
    }
    /**
     * @return array
     */
    private final function getWidgetStyles(){
        return $this->_styles[self::ASSET_TYPE_WIDGET];
    }
    /**
     * @return array
     */
    private final function getAdminScripts(){
        return $this->_scripts[self::ASSET_TYPE_ADMIN];
    }
    /**
     * @return array
     */
    private final function getWidgetScripts(){
        return $this->_scripts[self::ASSET_TYPE_WIDGET];
    }
    /**
     * Inicializa los assets del form de administración del widget
     */
    public final function initAdminAssets() {
        foreach ($this->getAdminStyles() as $style => $deps) {
            switch ($style) {
                case 'media-gallery':
                    wp_enqueue_style(
                            'coders-media-selector-style',
                            $this->assetUrl('media-gallery.css', false));
                    break;
                default:
                    wp_enqueue_style(
                            sprintf('coders-widget-%s-style', $style),
                            $this->assetUrl($style . '.css'),
                            $deps);
                    break;
            }
        }
        foreach ($this->getAdminScripts() as $script => $deps) {
            switch ($script) {
                case 'media-gallery':
                    //incluir las librerías de WP para el mediamanager
                    wp_enqueue_media();
                    wp_enqueue_script(
                            'coders-media-selector-script',
                            $this->assetUrl('media-gallery.js', false),
                            $deps);
                    break;
                default:
                    wp_enqueue_script(
                            sprintf('coders-widget-%s-script', $script),
                            $this->assetUrl($script . '.js'),
                            $deps);
                    break;
            }
        }
    }

    /**
     * Registra los assets del widget publico
     */
    public final function initWidgetAssets() {
        foreach( $this->getWidgetScripts() as $script => $deps ){
            wp_enqueue_script(
                    sprintf('coders-widget-%s-script',$script),
                    $this->assetPathUrl($script.'.js'),
                    $deps);
        }
        foreach( $this->getWidgetStyles() as $style => $deps ){
            wp_enqueue_style(
                    sprintf('coders-widget-%s-style',$style),
                    $this->assetPathUrl($style.'.css'),
                    $deps);
        }
    }
    /**
     * @param string $name
     * @param array $arguments
     */
    /*public function __call($name, $arguments) {
        
        if( $name === get_class($this) ){
        
            $widget = $this->setup();

            parent::WP_Widget(
                    isset($widget['id']) ? $widget['id'] : self::getWidgetId(), 
                    $widget['name'],
                    array('description' => $widget['description']));
        }
        elseif(method_exists(parent, '__call')){
            parent::__call( $name, $arguments );
        }
    }*/
    /**
     * @version 1.2 Requiere del uso de la clase ReflectionClass para conocer
     * la clase invocante y procedente
     * @return string
     */
    protected static final function getWidgetId( ){
        
        $called = new \ReflectionClass(get_called_class());
        $full_path = strtolower( $called->getFileName() );
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
     * @return string
     */
    protected static final function pluginPath(){
        return __DIR__ . '/widgets';
    }
    /**
     * @param string $widget
     * @return string
     */
    protected static final function widgetPath( $widget = '' ){
        
        return sprintf('%s/widgets/%s', self::pluginPath( ) , $widget );
    }
    /**
     * @param string $widget
     * @return string
     */
    protected static final function widgetUrl( $widget = '' ){
        
        return sprintf('%s/widgets/%s', self::pluginPath( ) , $widget );
    }
    /**
     * @param string $widget
     * @return string
     */
    protected static final function themePath( $widget = '' ){

        return sprintf( '%s/widgets/%s', get_stylesheet_directory(), $widget );
    }
    /**
     * @param string $widget
     * @return string
     */
    protected static final function themeUrl( $widget = '' ){

        return sprintf( '%s/widgets/%s', get_stylesheet_directory(), $widget );
    }
    /**
     * Cargar vista del widget, si exsite una vista personalizada, la selecciona, sino, recupera la original
     * Por defecto en la carpeta del widget, pero buscará primero si existe alguna vista en el tema
     * @param string $view
     * @return string
     */
    protected final function viewPath( $view = 'default' ){
        
        $widget_id = self::getWidgetId();
        
        $theme_path = self::themePath( $widget_id );
        
        $base_path = self::widgetPath( $widget_id );

        return sprintf('%s/html/%s.php' ,
                file_exists( $theme_path ) ? $theme_path : $base_path ,
                $view );
    }
    /**
     * Ruta del asset solicitado, estilo, script, ...
     * @param string $asset
     * @return string
     */
    protected static function assetPath( $asset ){

        $widget_id = self::getWidgetId();
        
        $theme_path = self::themePath( $widget_id );
        
        $base_path = self::widgetPath( $widget_id );
        
        return sprintf('%s/assets/%s',
                file_exists($theme_path) ? $theme_path : $base_path ,
                $asset );
    }
    /**
     * @param string $asset
     */
    protected static function assetUrl( $asset ){

        $widget_id = self::getWidgetId();
        
        $theme_path = self::themePath( $widget_id );
        $theme_url = self::themeUrl( $widget_id );
        $base_url = self::widgetUrl( $widget_id );
        
        return sprintf('%s/%s',
                file_exists($theme_path) ? $theme_url : $base_url,
                $asset);
    }
    /**
     * URL de estilos y scripts del widget, estilo, script, ...
     * @param string $asset
     * @return string
     */
    protected final function assetPathUrl( $asset , $widassetPath = true ){
        
        //probar primero a localizar un script o asset personalizado
        
        return WidgetManager::assetPathUrl($asset, $widassetPath ? self::getWidgetId() : null );
    }
    /**
     * Lista los archivos de tipo en el directorio de personalizaciones del tema
     * @param string $file_type
     * @return array
     */
    protected final function listThemeViews( $file_type = 'php' ){

        $output = array();
        $widget_id = self::getWidgetId( );
        $theme_path = self::themePath($widget_id);

        if ( strlen($file_type) > 0 && file_exists($theme_path) && $handle = opendir($theme_path)) {
            
            while (false !== ($file = readdir($handle)))
            {
                if( ( $offset = strrpos($file, '.' . $file_type ) ) !== false ){
                    $file_name = substr($file, 0 , $offset );
                    $output[ $file_name ] = sprintf('%s ( %s )',
                            $file_name ,
                            __('Custom View','coders_widget_pack'));
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
    protected final function listWidgetViews( ){

        $output = array();
        $widget_id = self::getWidgetId( );
        $html_path = sprintf('%s/html', self::widgetPath($widget_id) );

        if ( file_exists($html_path) && $handle = opendir($html_path)) {
            
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
                $this->registerAdminScript('media-gallery',array('jquery'))
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
                $checkbox = array(
                        'type'=>'checkbox',
                        'name'=>$this->get_field_name($input),
                        'id'=>$this->get_field_id($input),
                        'value'=>1,
                        'style'=>'float:right;');
                if(intval($value) > 0 ){
                    $checkbox['checked'] = 'checked';
                }
                print self::__HTML('button', $checkbox );
                break;
            case self::TYPE_MEDIA:
                $att_url = $value > 0 ? wp_get_attachment_url( $value ) : false;
                $image = ( $att_url !== false ) ?
                        self::__HTML('img', array('src'=>$att_url,'alt'=>get_the_title($value))) :
                        self::__HTML('span', array('class'=>'empty'),'<!-- media-not-found -->');
                print self::__HTML('button', array(
                    'class' => 'media-selector widefat ' . (intval($value) > 0 ? 'selected' : '' ),
                    'id' => $this->get_field_id($input),
                    'name' => $this->get_field_name($input),
                    'value' => $value
                ), $image);
                print self::__HTML('script', array(
                    'type' => 'text/javascript',
                ), sprintf( 'new codersMediaController("%s","%s")' ,
                        $this->get_field_id( $input ) ,
                        $this->get_field_name( $input ) ) );
                break;
            case self::TYPE_MEDIA_LIST:
                if( !is_array($value) ){
                    //printf('<!--%s-->',$value);
                    $value = explode(self::INPUT_ARRAY_SEPARATOR, $value);
                }
                $input_name = $this->get_field_name($input);
                $input_id = $this->get_field_id($input);
                $media_list = [];
                if(strlen($value[0])){
                    foreach( $value as $item ){
                        $item_id = self::__HTML('input', array(
                                    'type'=>'hidden',
                                    'name'=>$this->get_field_name($input).'[]',
                                    'value'=>$item));
                        $item_img = self::__HTML('img', array(
                            'src'=>wp_get_attachment_url($item),
                            'alt'=> get_the_title($item)));
                        $media_list[] = self::__HTML('li',
                                array('class'=>'media-item'),
                                $item_id . $item_img );
                    }
                }
                print self::__HTML('ul', array(
                    'class'=>'media-selector multiple',
                    'id'=>$input_id,
                    'name'=>$input_name),
                    $media_list);
                print self::__HTML('script',
                        array( 'type' => 'text/havascript' ),
                        sprintf('new codersMediaController("%s","%s")',$input_id,$input_name));
                break;
            case self::TYPE_FLOAT:
            case self::TYPE_NUMBER:
                $number = array(
                    'type' => 'number',
                    'name' => $this->get_field_name($input),
                    'id' => $this->get_field_id($input),
                    'value' => $value,
                    'step' => isset( $meta['step']) ? $meta['step'] : 1,
                    'min' => isset( $meta['min']) ? $meta['min'] : 0,
                );
                if( isset( $meta['max'])){
                    $number['max'] = $meta['max'];
                }
                print self::__HTML( 'input', $number);
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
                    print self::__HTML( 'i', array(
                        'class' => 'widefat alignright',
                        'style' => 'float: right;'
                    ), $value);
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
                printf('<input type="text" name="%s" id="%s" value="%s" class="widefat" %s />',
                        $this->get_field_name($input),
                        $this->get_field_id($input),
                        $value,
                        isset($meta['placeholder']) ? sprintf('placeholder="%s"',$meta['placeholder']) : '');
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
