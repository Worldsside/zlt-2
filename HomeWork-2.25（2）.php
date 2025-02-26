<?php
    //编程题：图书馆管理系统
    //题目描述
    //请设计一个简单的图书馆管理系统，包括以下内容：
    //
    //图书类（Book）：
    //
    //属性：
    //title（书名）
    //author（作者）
    //isbn（国际标准书号）
    //isAvailable（是否可借）
    //方法：
    //__construct($title, $author, $isbn)：构造函数，接收书名、作者和ISBN，并默认设置书籍为可借状态。
    //borrowBook()：将书籍状态设置为不可借，并返回借书成功的消息。
    //returnBook()：将书籍状态设置为可借，并返回还书成功的消息。
    //getBookInfo()：返回书籍的详细信息，包括书名、作者、ISBN 和可借状态。
    //
    //用户类（User）：
    //
    //属性：
    //name（用户姓名）
    //borrowedBooks（已借书籍，数组）
    //方法：
    //__construct($name)：构造函数，接收用户姓名，并初始化已借书籍为空数组。
    //borrowBook(Book $book)：借阅一本书，如果书籍可借，则将书籍添加到用户的已借书籍列表中，并调用书籍的 borrowBook() 方法。
    //returnBook(Book $book)：归还一本书，如果该书在用户的已借书籍列表中，则从列表中移除，并调用书籍的 returnBook() 方法。
    //getBorrowedBooks()：返回用户当前已借的所有书籍信息。
    //任务
    //实现 Book 和 User 类，按照上面描述的属性和方法。
    //编写一个简单的脚本，创建一些图书和用户对象，模拟借书和还书的过程，并输出相关信息。

    //图书类（Book）：
    class Book
    {
        //属性：
        //title（书名）
        //author（作者）
        //isbn（国际标准书号）
        //isAvailable（是否可借）
        var $title;
        var $author;
        var $isbn;
        var $isAvailable = 1;

        //方法：
        //__construct($title, $author, $isbn)：构造函数，接收书名、作者和ISBN，并默认设置书籍为可借状态。
        //borrowBook()：将书籍状态设置为不可借，并返回借书成功的消息。
        //returnBook()：将书籍状态设置为可借，并返回还书成功的消息。
        //getBookInfo()：返回书籍的详细信息，包括书名、作者、ISBN 和可借状态。

        function __construct($title, $author, $isbn)
        {
            $this->title = $title;
            $this->author = $author;
            $this->isbn = $isbn;
        }

        function borrowBook(): string
        {
            $this->isAvailable = 0;
            echo "\n[*]图书已成功借出，当前图书状态为：\n";
            foreach ($this->getBookInfo() as $key => $value) {
                echo $key . " => " . $value . "\n";
            }
            return "Book {$this->title} has been successfully borrowed!\n";
        }

        function returnBook(): string
        {
            $this->isAvailable = 1;
            echo "\n[*]图书已成功归还，当前图书状态为：\n";
            foreach ($this->getBookInfo() as $key => $value) {
                echo $key . " => " . $value . "\n";
            }
            return "Book {$this->title} has been successfully returned!\n";

        }

        function getBookInfo(): array
        {
            return [
                'title' => $this->title,
                'author' => $this->author,
                'isbn' => $this->isbn,
                'isAvailable' => $this->isAvailable
            ];
        }

    }
    //用户类（User）：
    class User
    {
        //属性：
        //name（用户姓名）
        //borrowedBooks（已借书籍，数组）
        var $name = '';
        var $borrowedBooks = array();
        //方法：
        //__construct($name)：构造函数，接收用户姓名，并初始化已借书籍为空数组。
        //borrowBook(Book $book)：借阅一本书，如果书籍可借，则将书籍添加到用户的已借书籍列表中，并调用书籍的 borrowBook() 方法。
        //returnBook(Book $book)：归还一本书，如果该书在用户的已借书籍列表中，则从列表中移除，并调用书籍的 returnBook() 方法。
        //getBorrowedBooks()：返回用户当前已借的所有书籍信息。
        public function __construct($name)
        {
            $this->name = $name;
            $this->borrowedBooks = array();
        }

        public function borrowBook(Book $book)
        {
            if($book->isAvailable) {
                $this->borrowedBooks[] = $book;
                echo $book->borrowBook();
                $this->getBorrowedBooks();
            }elseif($book->isAvailable == 0) {
                echo "\n[*]借阅失败：当前图书已借出。";
                $this->getBorrowedBooks();
            }
        }

        public function returnBook(Book $book)
        {
            $index = array_search($book, $this->borrowedBooks, true);
            if ($index !== false) {
                unset($this->borrowedBooks[$index]);
                echo $book->returnBook();
                $this->getBorrowedBooks();
            }
        }

        public function getBorrowedBooks()
        {
            if($this->borrowedBooks){
                $borrowed_num = 0;
                echo "\n[*]借阅人 {$this->name} 当前借阅书籍：\n";
                foreach ($this->borrowedBooks as $borrowedBook) {
                    $borrowed_num += 1;
                    echo "{$borrowed_num}、" . $borrowedBook->title . "\n";
                }
                echo "[*]共借阅{$borrowed_num}本。";
                echo "\n";
            }else{
                echo "\n[*]借阅人 {$this->name} ，当前无借阅书籍\n";
            }
        }
    }

    function addBook($books) {
        foreach($books as $book){
            $book_isbn = $book[2];
            $class_book[$book_isbn] = new Book($book[0],$book[1],$book[2]);
            $book_info = $class_book[$book_isbn]->getBookInfo();
            echo "[*]书籍已入库，书籍信息：\n";
            foreach ($book_info as $key => $value) {
                echo $key . " => " . $value . "\n";
            }
            echo "\n";
        }
        echo "当前共 " . count($class_book) . " 本书籍\n";
        return $class_book;
    }

    function addUser($users) {
        foreach($users as $username){
            $class_users[$username] = new User($username);
            echo "[*]借阅人 {$username} 已添加\n";
        }
        echo "\n当前共 ". count($class_users) . " 位借阅人\n";
        return $class_users;
    }

    //任务
    //实现 Book 和 User 类，按照上面描述的属性和方法。
    //编写一个简单的脚本，创建一些图书和用户对象，模拟借书和还书的过程，并输出相关信息。
    $books = array(array("红楼梦","曹雪芹",9787020002207),array("西游记","吴承恩",9787532512003),array("水浒传","施耐庵",9787020015016),array("三国演义","罗贯中",7806651098));
    $users = array("小明","小红","小王");

    $class_book = addBook($books);
    $class_users = addUser($users);

    $class_users["小明"]->borrowBook($class_book[9787020002207]);
    $class_users["小明"]->borrowBook($class_book[7806651098]);
    $class_users["小红"]->borrowBook($class_book[7806651098]);
    $class_users["小明"]->returnBook($class_book[9787020002207]);
    $class_users["小红"]->borrowBook($class_book[9787532512003]);
    $class_users["小明"]->returnBook($class_book[7806651098]);

