<?php
    //Todo List
    //1、受保护的方法如何访问（代码实现，要求有关键注释）
    //2、了解接口是什么，怎么用（代码写一个示例文件）

    //创建类
    class demo_protected {
        //创建保护方法
        protected function protected_method() {
            echo "Visit protected method!\n";
        }
        //在类内创建一个public的调用保护方法的函数以供间接调用
        public function call_proc_func() {
            $this->protected_method();
        }

    }
    //创建一个child类
    class demo_protected_child extends demo_protected {
        //在child类中创建一个public的调用保护方法的函数以供间接调用
        public function call_proc_func() {
            $this->protected_method();
        }
    }

    //1、在类内部重新生成一个调用保护方法的函数，并调用该函数来间接调用保护类
    $demo_1 = new demo_protected();
    echo "demo 1!\n";
    //$demo_1->protected_method();
    $demo_1->call_proc_func();

    //2、创建一个子类，并在子类中调用保护方法，然后调用该子类中的函数，实现间接调用
    $demo_2 = new demo_protected_child();
    echo "demo 2!\n";
    $demo_2->call_proc_func();

    //3、利用反射调用（互联网查询得知的使用方法），简单来说就是利用php本身的类来获取指定类和方法的实例，然后通过调用本身的类的实例来间接调用受保护的方法
    $demo_3  = new ReflectionClass("demo_protected");
    echo "demo 3!\n";
    $demo_3_method = $demo_3->getMethod("protected_method");
    $demo_3_method->setAccessible(true);
    $demo_3_instance = $demo_3->newInstance();
    $demo_3_method->invoke($demo_3_instance);



    //创建一个接口
    interface demo_interface
    {
        public function interface_func();
        public function interface_func_2();
    }

    //创建一个类调用接口
    class demo_interface_class implements demo_interface
    {
        private $word = "visit interface func";
        public function interface_func(){
            echo $this->word . __FUNCTION__;
        }
        public function interface_func_2(){
            echo $this->word . __FUNCTION__;
        }
    }

    $interface = new demo_interface_class();
    $interface->interface_func();
