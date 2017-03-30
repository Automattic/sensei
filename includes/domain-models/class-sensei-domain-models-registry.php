<?php


class Sensei_Domain_Models_Registry {

    private static $instance = null;
    protected $data_stores;

    private function __construct() {
        $this->field_declarations_by_model = array();
        $this->factories = array();
        $this->data_stores = array();
    }

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_factory( $klass ) {
        $klass = is_string( $klass ) ? $klass : get_class( $klass );
        $this->get_field_declarations( $klass );
        if ( !isset( $this->factories[$klass] ) ) {
            $this->factories[$klass] = new Sensei_Domain_Models_Factory ($klass, $this );
        }

        return $this->factories[$klass];
    }

    /**
     * @param $request
     * @return Sensei_Domain_Models_Course
     */
    public function new_from_request( $klass, $request )
    {
        $fields = $this->get_field_declarations( $klass );
        $field_data = array();
        foreach ($fields as $field) {
            if (isset($request[$field->name])) {
                $field_data[$field->name] = $request[$field->name];
            } else {
                $field_data[$field->name] = $field->get_default_value();
            }
        }

        return $this->create_object( $klass, $field_data );
    }

    public function all( $klass ) {
        $results = array();
        foreach ($this->get_entities( $klass ) as $entity) {
            $results[] = $this->create_object($klass, $entity);
        }
        return new Sensei_Domain_Models_Model_Collection($results);
    }

    public function find_one_by_id( $klass, $id) {
        $entity = $this->get_entity( $klass, $id );
        return !empty($entity) ? $this->create_object( $klass, $entity ) : null;
    }

    /**
     * @param $id unique id
     * @throws Sensei_Domain_Models_Exception
     * return object|null
     */
    public function get_entity( $klass, $id) {
        return $this->call_fn( $klass, 'get_entity', $id);
    }

    /**
     * @throws Sensei_Domain_Models_Exception
     * @return array
     */
    public function get_entities( $klass ) {
        return $this->call_fn( $klass, 'get_entities' );
    }

    private function call_fn() {
        $args = func_get_args();
        $klass = array_shift( $args );
        $fn_name = array_shift( $args );
        return call_user_func_array( array( $this->get_domain_model_class( $klass ), $fn_name ), $args );
    }

    public function create_object( $klass, $entity) {
        $klass = $this->get_domain_model_class( $klass );
        return new $klass( $entity );
    }

    private function get_domain_model_class( $thing ) {
        $thing = $this->ensure_class_string( $thing );

        $this->get_field_declarations( $thing );

        if (!in_array( $thing, array_keys( $this->field_declarations_by_model ))) {
            throw new Sensei_Domain_Models_Exception('Domain Model not registered');
        }
        return $thing;
    }

    public function get_field_declarations( $klass, $filter_by_type=null ) {
        $super = 'Sensei_Domain_Models_Model_Abstract';
        if ( !is_subclass_of( $klass, $super ) ) {
            throw new Sensei_Domain_Models_Exception( $klass . ' is not a subclass of ' . $super );
        }

        if ( !isset($this->field_declarations_by_model[$klass]) ||
            null === $this->field_declarations_by_model[$klass] ) {
            // lazy-load model declarations when the first model if this type is constructed
            $fields = call_user_func( array( $klass, 'declare_fields' ) );
            $this->field_declarations_by_model[$klass] = call_user_func( array( $klass, 'initialize_field_map' ), $fields );
        }
        if ( null === $filter_by_type ) {
            return $this->field_declarations_by_model[$klass];
        }
        $filtered = array();
        foreach ($this->field_declarations_by_model[$klass] as $field_declaration ) {
            if ( $field_declaration->type === $filter_by_type ) {
                $filtered[] = $field_declaration;
            }
        }
        return $filtered;
    }

    /**
     * @param $type_class string the sensei domain model class
     * @param $data_store Sensei_Domain_Models_Data_Store the data store instance
     * @return $this
     */
    public function set_data_store_for_domain_model( $type_class, $data_store ) {
        return $this->set_data_store( $type_class, $data_store );
    }

    /**
     * @param $type_class string
     * @return Sensei_Domain_Models_Data_Store
     * @throws Sensei_Domain_Models_Exception
     */
    public function get_data_store_for_domain_model( $type_class ) {
        $type_class = $this->ensure_class_string( $type_class );
        if (!isset( $this->data_stores[$type_class] ) ) {
            throw new Sensei_Domain_Models_Exception( 'No datastore set for class ' . $type_class );
        }
        return $this->get_data_store( $type_class );
    }

    /**
     * @param $name string
     * @param $data_store_instance Sensei_Domain_Models_Data_Store
     * @return $this
     */
    public function set_data_store($name, $data_store_instance ) {
        $this->data_stores[$name] = $data_store_instance;
        return $this;
    }

    /**
     * @param $name
     * @return null|Sensei_Domain_Models_Data_Store
     */
    public function get_data_store( $name ) {
        if ( isset( $this->data_stores[ $name ] ) ) {
            return $this->data_stores[ $name ];
        }
        return null;
    }

    private function ensure_class_string( $thing ) {
        if (!is_string( $thing ) ) {
            return get_class( $thing );
        }
        return $thing;
    }
}