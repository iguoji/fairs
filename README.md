商城系统

1. 账户列表，是否绑卡筛选


```php

class Signin {
    /**
     * 参数验证
     */
    public function validate(Request $req) {

    }

    /**
     * 逻辑处理
     */
    public function handle(Request $req, Response $res) {
        // 身份验证
        $role = $this->verify();
        // 参数验证
        $data = $this->validate($req)
        // 逻辑处理
        $result = $this->luoji($data)

        // 返回模板
        $res->html('/xxx.html');
        // 返回JSON
        $res->json([], 'error', 200);
        // 页面跳转
        $res->redirect('http://url');
        // 文件下载
        $res->download('file.ext');
        // 文件显示
        $res->file('image/png')
    }

    /**
     * 普通会员
     */
    public function member() {

    }

    /**
     * 后台管理
     */
    public function manager() {

    }
}



return [
    'admin.example.com'     =>  [
        '/'                 =>  \App\Admin\Index::class,
        '/signin'           =>  \App\Rbac\Signin::class,
        '/signup'           =>  \App\Rbac\Signup::class,


        '/catalogs'         =>  \App\Catalog\Index::class,
        '/catalog/save'     =>  \App\Catalog\Save::class,
        '/catalog/edit'     =>  \App\Catalog\Edit::class,
        '/catalog/read'     =>  \App\Catalog\Read::class,
        '/catalog/remove'   =>  \App\Catalog\Remove::class,
    ],

    'www.example.com'       =>  [
        '/catalogs'         =>  \App\Catalog\Index::class,
        '/catalog/save'     =>  \App\Catalog\Save::class,
        '/catalog/edit'     =>  \App\Catalog\Edit::class,
        '/catalog/read'     =>  \App\Catalog\Read::class,
        '/catalog/remove'   =>  \App\Catalog\Remove::class,
    ],
];
```