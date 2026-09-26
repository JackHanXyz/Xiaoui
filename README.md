# Xiaoui

一个轻量、现代、零依赖的 PHP Web 框架，兼顾 MVC 与 RESTful API 开发。

> **Xiaoui** 是一个核心骨架级的框架，目标是用最小的 API 面积覆盖日常 Web 服务 90% 的需求：
> 路由、中间件、依赖注入、请求/响应、校验、以及一个可选的 PDO 查询构建器。
> 没有强制的目录约定，没有魔法，读一遍 `src/` 就能看懂全部实现。

[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

---

## 特性

- **零第三方依赖**：核心只依赖 PHP 8.1+ 与标准库，无框架包袱。
- **PSR 风格接口**：容器兼容 PSR-11，请求处理与中间件遵循 PSR-15 语义。
- **反射自动装配**：构造函数依赖自动解析，无需手动注册。
- **灵活路由**：`GET/POST/PUT/PATCH/DELETE`、路径参数、路由分组、全局中间件。
- **轻量校验器**：`required / email / min / max / in` 等规则。
- **可选数据库层**：PDO 连接管理 + 链式查询构建器（MySQL / PostgreSQL / SQLite）。

## 快速开始

```bash
composer install

# 本地开发服务器
composer serve
# 或
php -S localhost:8000 -t public
```

访问 <http://localhost:8000> 应返回：

```json
{"framework":"Xiaoui","message":"Hello, World!","time":"..."}
```

## 目录结构

```
Xiaoui/
├── app/
│   ├── Controllers/          # 控制器
│   └── Middleware/           # 中间件
├── config/                   # 配置文件（返回数组）
├── public/                   # Web 根目录（唯一可公开访问的入口）
│   └── index.php
├── routes/                   # 路由定义
│   └── web.php
├── src/                      # 框架核心（命名空间 Xiaoui\）
│   ├── Application.php       # 应用与容器
│   ├── Kernel.php            # HTTP 内核（中间件调度）
│   ├── Container/            # DI 容器
│   ├── Database/             # PDO + 查询构建器
│   ├── Http/                 # Request / Response / 中间件接口
│   ├── Routing/              # Router / Route / RouteCollection
│   ├── Support/              # Config / Env
│   └── Validation/           # 校验器
└── tests/                    # PHPUnit 测试
```

## 路由

```php
use Xiaoui\Routing\Router;

$router->get('/', [HomeController::class, 'index']);       // 控制器
$router->get('/users/{id}', fn (string $id) => ['id' => $id]); // 闭包 + 路径参数
$router->post('/users', [UserController::class, 'store']);
$router->group(['prefix' => '/api', 'middleware' => [AuthMiddleware::class]], function (Router $r) {
    $r->get('/status', fn () => ['status' => 'ok']);
});
```

## 中间件

实现 `Xiaoui\Http\MiddlewareInterface`：

```php
class Auth implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (!$request->header('authorization')) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        return $handler->handle($request);
    }
}
```

## 依赖注入

```php
class UserController
{
    public function __construct(private UserRepository $repository) {}

    public function index(): Response
    {
        return Response::json($this->repository->all());
    }
}
```

构造函数参数由容器自动解析，无需手动绑定。

## 配置

配置文件放在 `config/` 目录，每个文件返回一个数组，文件名即配置的顶层键。
应用启动时由 `Application::loadConfig()` 自动加载，通过 `config()` 点号取值：

```php
// config/app.php 返回 ['name' => 'Xiaoui', 'debug' => true]

$app->config('app.name');   // 'Xiaoui'
$app->config('app.debug');  // true
```

环境变量通过 `.env` 文件加载（参考 `.env.example`），`getenv()` 可直接读取。

## 校验

```php
use Xiaoui\Validation\Validator;

$validator = Validator::make($request->all(), [
    'email' => 'required|email',
    'age'   => 'required|integer|min:18',
]);

if ($validator->fails()) {
    return Response::json(['errors' => $validator->errors()], 422);
}
```

## 数据库（可选）

```php
$db = new Xiaoui\Database\QueryBuilder(new Xiaoui\Database\Connection($config));

$users = $db->table('users')
    ->where('active', true)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

## 测试

```bash
composer test
```

## 许可

[MIT](LICENSE) — 可自由商用，无需公开你的衍生代码。
