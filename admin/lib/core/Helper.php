<?php 
if( ! $this->config[$this->plugins_slug]['MONDIRA_PLUGINS_SLUG']  ) { die( 'Sorry to say, you can not access this page on this way!' ); }
    if(!class_exists('Helper')){
        abstract class Helper {
        protected $info;

        public function __construct( $info ) {
            $this->info = $info;
            
            if(empty($this->info['field_type']))
                $this->info['field_type'] = 'option';
        }
        
        
        
        public function createForm($attr = array('method'=>'post', 'action'=>'', 'name'=>'form', 'type'=>'multipart/form-data', 'add_slug'=>'yes')){
            
            $default = array('method'=>'post', 'action'=>'', 'name'=>'form', 'type'=>'multipart/form-data', 'add_slug'=>'yes');
            $attr = array_merge($default, $attr);
            
            $output = '<form';
            foreach($attr as $key=>$value){
                $output.=' ' . $key . '="' . $value . '"';
            }
            $output.= '>';
            
            if(!empty($attr['add_slug']) && 'yes'==$attr['add_slug'])
                $output.= $this->input(array('type'=>'hidden', 'value'=>$this->info['slug'], 'name'=>'slug', 'add_slug'=>'no'));
            
            $output.="\n";
            return $output;
        }
        
        public function endForm(){
            $output = '</form>';
            $output.="\n";
            return $output;
        }
        
        function input($attr = array()){
            if(empty($attr['name']))
                return false;
            
            $default = array('name'=>'name', 'type'=>'text', 'id'=>'', 'class'=>'regular-text', 'value'=>'', 'add_slug'=>'yes');
            $attr = array_merge($default, $attr);
            
            if('yes' == $attr['add_slug'] && !empty($attr['name'])){
                
                if($this->info['field_type']=='meta')
                $attr['name'] = $this->info['slug'] . $attr['name'];
                else
                $attr['name'] = $this->info['slug'] . '['.$attr['name'].']';
            }
            
            
            $output = '<input';
            foreach($attr as $key=>$value){
                if ( !is_array( $value ) ) {
                    $output.=' ' . $key . '="' . $value . '"';
                }
            }
            $output.= ' />';
            
            return $output;
        }
        
            
        function textarea($attr = array()){
            if(empty($attr['name']))
                return false;
            
            $default = array('name'=>'name', 'type'=>'textarea', 'id'=>'', 'class'=>'regular-text', 'value'=>'', 'add_slug'=>'yes', 'cols'=>90, 'rows'=>5);
            $attr = array_merge($default, $attr);
            
            if('yes' == $attr['add_slug'] && !empty($attr['name'])){
                
                if($this->info['field_type']=='meta')
                $attr['name'] = $this->info['slug'] . $attr['name'];
                else
                $attr['name'] = $this->info['slug'] . '['.$attr['name'].']';
            }
            
            
            unset($attr['add_slug']);
            unset($attr['avoid_br']);
            
            if ( $attr[ 'type' ] == 'editor' ) {
                
                //Fix for WP 3.9 as It is not taking any id as array for wp_editor
                if ( is_version( '3.9' ) || true ) {
                    $editor_id = $attr['id'];    
                } else {
                    $editor_id = $attr['name'];                    
                } 
                
                ob_start();
                $settings = array();
                $val = str_replace(array('\\'), '', $attr['value']);
                wp_editor( stripcslashes($val), $editor_id, $settings );
                $output = ob_get_clean();    
            } else {
                $output = '<textarea';
                foreach ( $attr as $key => $value ) {
                    if ( $key != 'value' && !is_array( $value ) ) {
                        $output.=' ' . $key . '="' . $value . '"';
                    }
                }
                $output.= '>';
                $output.=$attr[ 'value' ];
                $output.='</textarea>';
            }
            
            
            
            return $output;
        }
        
             
        function select($attr = array()){
            if(empty($attr['name']))
                return false;
            
            
            $default = array('name'=>'name', 'id'=>'', 'class'=>'regular-select', 'empty'=>'Select...',  'options'=>array(), 'add_slug'=>'yes');
            $attr = array_merge($default, $attr);
            
            if('yes' == $attr['add_slug'] && !empty($attr['name'])){
                
                if($this->info['field_type']=='meta')
                $attr['name'] = $this->info['slug'] . $attr['name'];
                else
                $attr['name'] = $this->info['slug'] . '['.$attr['name'].']';
            }
            
            $value_arr = explode(',', $attr['selected']);
            
            $output = '<select';
            foreach($attr as $key=>$value){
                if($key=='options' || $key=='empty' || $key=='add_slug' || $key=='selected'){
                    
                }else {
                    $output.=' ' . $key . '="' . $value . '"';    
                }
                
            }
            $output.= ' />';
            $output.="\n";
            
            
            if(!empty($attr['options']) && is_array($attr['options'])){
                
                if(!empty($attr['nasted'])){
                    echo $attr['options'];
                } else {
                    if(!empty($attr['empty']))
                        $output.= '<option value="">'.$attr['empty'].'</option>';        
                    foreach($attr['options'] as $key=>$value)    {
                        if(!empty($attr['multiple']) && in_array($key, $value_arr)){
                            $output.= '<option value="'.$key.'" selected="selected">'.$value.'</option>';    
                        } else if(!empty($attr['selected']) && $key==$attr['selected']){
                            $output.= '<option value="'.$key.'" selected="selected">'.$value.'</option>';    
                        } else {
                            $output.= '<option value="'.$key.'">'.$value.'</option>';        
                        }
                        $output.="\n";
                    }    
                }
                
                
            }
            
            
            $output.="\n";
            $output.= '</select>';
            return $output;
        }
        
            
        function option($attr = array()){
            if(empty($attr['name']))
                return false;
            
            
            $default = array('name'=>'name', 'id'=>'', 'class'=>'regular-option', 'empty'=>'',  'options'=>array(), 'add_slug'=>'yes');
            $attr = array_merge($default, $attr);
            
            if('yes' == $attr['add_slug'] && !empty($attr['name'])){
                if($this->info['field_type']=='meta') {
                    $attr['name'] = $this->info['slug'] . $attr['name'];
                } else {
                    $attr['name'] = $this->info['slug'] . '['.$attr['name'].']';
                }   
            }
            
            //$attr['selected']
            
            $output = '';    
            if(!empty($attr['options']) && is_array($attr['options'])){
                $output.= '<fieldset><legend class="screen-reader-text"><span>'.$attr['title'].'</span></legend>'; 
                foreach($attr['options'] as $k=>$v){
                    if(!empty($attr['selected']) && $attr['selected']==$k){
                        $output.= '<label><input name="'.$attr['name'].'" class="'.$attr['class'].'" checked="checked" type="radio" value="'.$k.'">'.$v.'</label>';                   
                    } else {
                        $output.= '<label><input name="'.$attr['name'].'" class="'.$attr['class'].'" type="radio" value="'.$k.'">'.$v.'</label>';                   
                    }
                }
                $output.= '</fieldset>';    
            }
            
            return $output;
        }
        
            
        public function createFormTable($attr = array('method'=>'post', 'action'=>'', 'name'=>'form', 'type'=>'multipart/form-data')){
            
            $default = array('method'=>'post', 'action'=>'', 'name'=>'form', 'type'=>'multipart/form-data');
            $attr = array_merge($default, $attr);
            
            $output = '<form';
            foreach($attr as $key=>$value){
                $output.=' ' . $key . '="' . $value . '"';
            }
            $output.= '>';
            $output.="\n";
            $output.= $this->input(array('type'=>'hidden', 'value'=>$this->info['slug'], 'name'=>'slug', 'add_slug'=>'no'));
            $output.="\n";
            $output.= $this->tableStart();
            return $output;
        }
        
        public function endFormTable(){
            $output= $this->tableEnd();
            $output.= $this->saveChangeButton();
            $output.= $this->endForm();
            return $output;
        }
        
        public function saveChangeButton(){
            $output = '<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"  /></p>';
            return $output;
        }
        
        public function tableStart($attr = array('class'=>'form-table')){
            
            $default = array('class'=>'form-table');
            $attr = array_merge($default, $attr);
            
            $output = '<table';
            foreach($attr as $key=>$value){
                $output.=' ' . $key . '="' . $value . '"';
            }
            $output.= '>';
            $output.="\n";
            return $output;
        }
        
        public function tableEnd(){
            $output = '</table>';
            $output.="\n";
            return $output;
        }
        
        
        
        public function tableTr($attr = array('valign'=>'top')){
            
            $default = array('valign'=>'top');
            $attr = array_merge($default, $attr);
            
            
            $output = '<tr';
            foreach($attr as $key=>$value){
                $output.=' ' . $key . '="' . $value . '"';
            }
            $output.= '>';
            $output.="\n";
            return $output;
        }
        
        public function tableTrEnd(){
            $output = '</tr>';
            $output.="\n";
            return $output;
        }
        
        
        public function tableTh($attr = array('scope'=>'row')){
            
            $default = array('scope'=>'row');
            $attr = array_merge($default, $attr);
            
            
            $output = '<th';
            foreach($attr as $key=>$value){
                $output.=' ' . $key . '="' . $value . '"';
            }
            $output.= '>';
            $output.="\n";
            return $output;
        }
        
        public function tableThEnd(){
            $output = '</th>';
            $output.="\n";
            return $output;
        }
        
        
        public function tableTd($attr = array()){
            $output = '<td';
            foreach($attr as $key=>$value){
                $output.=' ' . $key . '="' . $value . '"';
            }
            $output.= '>';
            $output.="\n";
            return $output;
        }
        
        public function tableTdEnd(){
            $output = '</td>';
            $output.="\n";
            return $output;
        }
        
        
        public function label($attr = array('for'=>'', 'title'=>'')){
            
            $default = array('for'=>'', 'title'=>'');
            $attr = array_merge($default, $attr);
            
            
            $output = $this->labelStart($attr);
            $output.= $attr['title'];
            $output.= $this->labelEnd();
            
            return $output;
        }
        
        public function labelStart($attr = array('for'=>'')){
            
            $default = array('for'=>'');
            $attr = array_merge($default, $attr);
            
            
            $output = '<label';
            foreach($attr as $key=>$value){
                $output.=' ' . $key . '="' . $value . '"';
            }
            $output.= '>';
            $output.="\n";
            return $output;
        }
        
        public function labelEnd(){
            $output = '</label>';
            $output.="\n";
            return $output;
        }
        
        
        public function span($attr = array(), $content){
            $output = $this->spanStart($attr);
            $output.= $content;
            $output.= $this->spanEnd();
            
            return $output;
        }
        
        public function spanStart($attr = array()){
            $output = '<span';
            foreach($attr as $key=>$value){
                $output.=' ' . $key . '="' . $value . '"';
            }
            $output.= '>';
            $output.="\n";
            return $output;
        }
        
        public function spanEnd(){
            $output = '</span>';
            $output.="\n";
            return $output;
        }
        
            
        public function div($attr = array()){
            $output = $this->divStart($attr);
            $output.= $attr['content'];
            $output.= $this->divEnd();
            
            return $output;
        }
        
        public function divStart($attr = array()){
            $output = '<div';
            foreach($attr as $key=>$value){
                $output.=' ' . $key . '="' . $value . '"';
            }
            $output.= '>';
            $output.="\n";
            return $output;
        }
        
        public function divEnd(){
            $output = '</div>';
            $output.="\n";
            return $output;
        }
        
        
        
        
        
        
        public function createInputTitle($str){
            $expStr = explode('_', $str);
            $str = implode(' ', $expStr);
            $str = strtolower($str);
            $str = ucwords($str);
            return $str;
        }
        
        
        public function stringToId($str){
            $expStr = explode(' ', $str);
            $str = implode('_', $expStr);
            $str = strtolower($str);
            return $str;
        }
        
        
        
        abstract function init();
    }
}
