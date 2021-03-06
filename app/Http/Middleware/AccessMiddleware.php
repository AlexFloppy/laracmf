<?php

namespace App\Http\Middleware;

use Closure;
use GrahamCampbell\Credentials\Credentials;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AccessMiddleware
{
    /**
     * The credentials instance.
     *
     * @var \GrahamCampbell\Credentials\Credentials
     */
    protected $credentials;

    /**
     * The logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Create a new instance.
     *
     * @param \GrahamCampbell\Credentials\Credentials $credentials
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(Credentials $credentials, LoggerInterface $logger)
    {
        $this->credentials = $credentials;
        $this->logger = $logger;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$this->credentials->check()) {
            $this->logger->info('User tried to access a page without being logged in', ['path' => $request->path()]);
            if ($request->ajax()) {
                throw new UnauthorizedHttpException('Action Requires Login');
            }

            return Redirect::guest(route('account.login'))
                ->with('error', 'You must be logged in to perform that action.');
        }

        return $next($request);
    }

    /**
     * Get the required authentication level.
     *
     * We're using reflection here to grab the short class name of the
     * extending class, and then returning the lowercase value.
     *
     * @return string
     */
    protected function level()
    {
        $reflection = new ReflectionClass($this);

        $level = $reflection->getShortName();

        return strtolower($level);
    }

    /**
     * Get credentials instance.
     *
     * @return \GrahamCampbell\Credentials\Credentials
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * Get logger instance.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}