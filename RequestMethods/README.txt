The namespace  \Core\RequestMethods
provides the following method attributes:

Note: All methods with these attributes take Request as parameter.

 * StartUp([string $path])
    for methods that need to be run before other methods in the same module.

 * GET/POST/PUT/DELETE(string $path)
    for handling requests of there types. 

 * Fallback([string $path])
    methods called when there isn't any method to handle the given request.

$path - resource path. (example: "/user/profile")
The path can include arguments, (example: "/user/{id}/profile")
which later can be retrieved from the Request object. Example:

> # Modules/Foo.php
>
> use Core\RequestResponse;
> use Core\RequestMethods\GET;
>
> class Foo
> {
>     #[GET("/foo/{bar}")]
>     public function Baz(Request $req) : RequestResponse
>     {
>         ...
>         $req->bar;
>         $req->arg("bar");
>         ...
>     }
> }

Setting $path on StartUp or Fallback method will call this method
only if the requrested resource is within this path.
If there are multiple StartUp methods matching the request, they all are called.
If there are multiple Fallback methods, only the closest matches are called.
For example, if we have:
> #[StartUp] function A();
> #[StartUp("/foo")] function B();
> #[StartUp("/bar")] function C();
> #[Fallback("/")] function D();
> #[Fallback("/bar")] function E();
a request to /foo will call A, B and then D.
a request to /bar will call A, C and then E.
