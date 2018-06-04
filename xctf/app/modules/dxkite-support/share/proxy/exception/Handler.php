<?php
namespace dxkite\support\proxy\exception;

class Handler
{
    public static function uncaughtException($exception)
    {
        $exception=new \suda\core\Exception($exception);
        if (request()->isJson() || request()->hasHeader('debug')) {
            $response=new class extends \suda\core\Response {
                public $exception;

                public function onRequest(\suda\core\Request $request)
                {
                    $this->state(500);
                    debug()->logException($this->exception);
                    $this->error($this->exception->getName(), $this->exception->getCode(), $this->exception->getMessage());
                }
                
                protected function error(string $name,int $code, string $message)
                {
                    $error=[
                        'error'=>[
                            'name'=>$name,
                            'message'=>$message,
                            'code'=>$code,
                            'data'=>[
                                'file'=>$this->exception->getFile(),
                                'line'=>$this->exception->getLine()
                            ],
                        ],
                        'id'=>null
                    ];
                    if (conf('debug')) {
                        $error['error']['data']['backtrace']=$this->exception->getBackTrace();
                    }
                    return $this->returnJson($error);
                }

                protected function returnJson(array $json)
                {
                    if ($callback=request()->get('jsonp_callback')) {
                        $this->type('js');
                        echo $callback.'('.json_encode($json).');';
                    } else {
                        return $this->json($json);
                    }
                }
            };
            $response->exception=$exception;
            $response->onRequest(request());
            exit;
        }
        return false;
    }
}
