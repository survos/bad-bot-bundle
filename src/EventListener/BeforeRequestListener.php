<?php

namespace Survos\BadBotBundle\EventListener;

use Inspector\Inspector;
use Inspector\Symfony\Bundle\Listeners\InspectorAwareTrait;
use Survos\BadBotBundle\Service\BotService;
use Survos\KeyValueBundle\Entity\KeyValueManagerInterface;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\When;
use Symfony\Contracts\Cache\CacheInterface;

class BeforeRequestListener
{
//    use InspectorAwareTrait;
    public function __construct(
        private BotService $botService,
        private KeyValueManagerInterface $kvManager,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
        protected ?Inspector $inspector=null,
        private ?CacheInterface $cache=null,
    ) {
    }


    #[AsEventListener(ExceptionEvent::class, priority: 10000)]
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception::class === NotFoundHttpException::class) {
            $paths = $this->botService->getProbePaths();
            $request = $event->getRequest();
            $ip = $request->getClientIp();
            $path = $request->getPathInfo();
            foreach ($paths as $pathRegex) {
                if (preg_match($pathRegex, $path)) {
                    $this->botService->ban($ip, $path);
                }
            }

        }
    }
    #[AsEventListener(RequestEvent::class, priority: 10000)]
    public function onKernelRequest(RequestEvent $event): void
    {
        // always let debug path through
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if (str_starts_with($path, '/_wdt')) {
            return;
        }

        // @todo: not found listener would be better.
        $paths = $this->botService->getProbePaths();
        $ips = $this->botService->getBannedIps();

        // check the IP
        $ip = $request->getClientIp();
        if ($this->botService->isBanned($ip)) {
            $response = $this->blockBot();
            // Send response
            $event->setResponse($response);
            return;
        }
        //
        $bot = $event->getRequest()->headers->get('User-Agent');

        // https://symfonycasts.com/screencast/question-answer-day/symfony2-dynamic-subdomains


        // @todo: get bad list from cache or config or something
        if (false)
        if (str_ends_with($path, '.php')
            || preg_match('{\.(alfa|php|php7)$}i', $path)
            || preg_match('{/wp-includes|/wp-content|.well-known|/wp-admin|/session}', $path)
        ) {
            $response = new Response();

            // from https://ourcodeworld.com/articles/read/1097/how-to-restrict-the-access-to-your-website-to-a-specific-country-geoblocking-in-symfony-5
            // https://lindevs.com/block-access-by-ip-address-in-symfony
            // https://blowstack.com/blog/how-to-use-events-listeners-in-symfony
            // https://medium.com/@dams_crr/mastering-symfonys-kernel-events-listeners-vs-subscribers-54be05bbe8fa
            $response->setStatusCode(Response::HTTP_FORBIDDEN);

            // Render some twig view, in our case we will render the blocked.html.twig file
            $response->setContent("<html lang='en'><body>Bad bot! $path</body></html>");
            // Return an HTML file
            $response->headers->set('Content-Type', 'text/html');
            if ($this->inspector?->isRecording()) {
                $this->inspector?->stopRecording();
            }
            $this->inspector->transaction()?->addContext("Referrer", $request->headers->get('referer'));

            // Send response
            $event->setResponse($response);

//            $redirect =  new RedirectResponse($url, 302);
//            $event->setResponse($redirect);
//                dd($host, $url, $this->baseHost);
//            dd($redirect, $url);
            return;

        }
    }

    private function blockBot(): Response
    {
        $response = new Response();

        $response->setStatusCode(Response::HTTP_FORBIDDEN);

        // Render some twig view, in our case we will render the blocked.html.twig file
        $response->setContent("<html lang='en'><body>Bad bot!</body></html>");
        // Return an HTML file
        $response->headers->set('Content-Type', 'text/html');
        if ($this->inspector?->isRecording()) {
            $this->inspector->stopRecording();
        }
//        $this->inspector->transaction()?->addContext("Referrer", $request->headers->get('referer'));

        return $response;

    }

}
