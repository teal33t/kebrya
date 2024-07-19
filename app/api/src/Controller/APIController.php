<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Jyotish\Lib;
// use Jyotish\Draw;
use Psr\Log\LoggerInterface;
class APIController extends AbstractController
{
    private $logger;
    private $chart;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->chart = new Lib();
    }

    /**
     * @Route("/ping", name="ping", methods={"GET"})
     */
    public function ping()
    {
        return $this->json([
            'pong' => "success",
        ], 200);
    }


    /**
     * @Route("/example", name="example", methods={"GET"})
     */
    public function example()
    {
        // $this->logger->info('API index endpoint accessed');
        $startTime = microtime(true);

        $now = $this->chart->calculator();
        $this->logger->debug('Chart calculated successfully');

        $endTime = microtime(true);
        $duration = number_format($endTime - $startTime, 3);
        $createdAt = (new \DateTime())->format('Y-m-d H:i:s');


        $htmlResponse = "
            <html>
            <head>
                <title>North Indian Birth Chart</title>
            </head>
            <body>
                <h1>North Indian Birth Chart</h1>
                <div>

                </div>
                <p>Response Duration: $duration seconds</p>
                <p>Generated At: $createdAt</p>
            </body>
            </html>
        ";

        return new Response($htmlResponse);
    }

    /**
     * @Route("/api", name="index", methods={"GET"})
     */
    public function index(): JsonResponse
    {
        $this->logger->info('API index endpoint accessed');
        $startTime = microtime(true);

        try {
            $now = $this->chart->calculator();
            $this->logger->debug('Chart calculated successfully');

            $endTime = microtime(true);
            $duration = number_format($endTime - $startTime, 3);
            $createdAt = (new \DateTime())->format('Y-m-d H:i:s');

            $response = [
                'chart' => $now,
                'duration_of_response' => (float) $duration,
                'created_at' => $createdAt,
            ];

            $this->logger->info('Returning successful response', $response);

            return $this->json($response);
        } catch (\Exception $e) {
            $this->logger->error('An error occurred: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->json([
                'error' => 'An internal error occurred',
            ], 500);
        }
    }


    /**
     * @Route("/api/transit-chart", name="calculate_transit_chart", methods={"POST"})
     */
    public function calculateTransitChart(Request $request): JsonResponse
    {
        $this->logger->info('Calculate transit endpoint accessed');
        $startTime = microtime(true);

        try {
            $params = json_decode($request->getContent(), true);
            
            // Set default values for optional parameters
            $params['t_sec'] = $params['t_sec'] ?? 0;
            $params['convert_from'] = 'gregorian';
            $params['convert_to'] = 'gregorian';
            // Add transit to infolevel
            $params['infolevel'] = $params['infolevel'] ?? [];
            if (!in_array('transit', $params['infolevel'])) {
                $params['infolevel'][] = 'transit';
            }

            $result = $this->chart->calculator($params);
            $this->logger->debug('Transit calculated successfully');

            $endTime = microtime(true);
            $duration = number_format($endTime - $startTime, 3);
            $createdAt = (new \DateTime())->format('Y-m-d H:i:s');

            $response = [
                'chart' => $result,
                'duration_of_response' => (float) $duration,
                'created_at' => $createdAt,
            ];

            $this->logger->info('Returning successful response', $response);

            return $this->json($response);
        } catch (\Exception $e) {
            $this->logger->error('An error occurred: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->json([
                'error' => 'An internal error occurred',
            ], 500);
        }
    }




    /**
     * @Route("/api/jalali/transit-chart", name="calculate_transit_jalali_chart", methods={"POST"})
     */
    public function calculateTransitJalaliChart(Request $request): JsonResponse
    {
        $this->logger->info('Calculate transit endpoint accessed');
        $startTime = microtime(true);

        try {
            $params = json_decode($request->getContent(), true);
            $params['convert_from'] = 'jalali';
            $params['convert_to'] = 'jalali';

            // Set default values for optional parameters
            $params['t_sec'] = $params['t_sec'] ?? 0;
            
            // Add transit to infolevel
            $params['infolevel'] = $params['infolevel'] ?? [];
            if (!in_array('transit', $params['infolevel'])) {
                $params['infolevel'][] = 'transit';
            }

            $result = $this->chart->calculator($params);
            $this->logger->debug('Transit calculated successfully');

            $endTime = microtime(true);
            $duration = number_format($endTime - $startTime, 3);
            $createdAt = (new \DateTime())->format('Y-m-d H:i:s');

            $response = [
                'chart' => $result,
                'duration_of_response' => (float) $duration,
                'created_at' => $createdAt,
            ];

            $this->logger->info('Returning successful response', $response);

            return $this->json($response);
        } catch (\Exception $e) {
            $this->logger->error('An error occurred: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->json([
                'error' => 'An internal error occurred',
            ], 500);
        }
    }


    /**
     * @Route("/api/moon-phase", name="calculate_moon_phase", methods={"POST"})
     */
    public function calculateMoonPhase(Request $request): JsonResponse
    {
        $this->logger->info('Calculate moon phase endpoint accessed');
        $startTime = microtime(true);

        try {
            $params = json_decode($request->getContent(), true);
            
            // Set default values for optional parameters
            $params['infolevel'] = [];
            $params['convert_from'] = 'gregorian';
            $params['convert_to'] = 'gregorian';

            $result = $this->chart->calculator($params);
            $moonPhase = $this->chart->calculateMoonPhaseChart($result);

            $endTime = microtime(true);
            $duration = number_format($endTime - $startTime, 3);
            $createdAt = (new \DateTime())->format('Y-m-d H:i:s');

            $response = [
                'moon_phase' => $moonPhase,
                'duration_of_response' => (float) $duration,
                'created_at' => $createdAt,
            ];

            $this->logger->info('Returning successful response', $response);

            return $this->json($response);
        } catch (\Exception $e) {
            $this->logger->error('An error occurred: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->json([
                'error' => 'An internal error occurred',
            ], 500);
        }
    }

    
    /**
     * @Route("/api/panchanga", name="calculate_panchanga", methods={"POST"})
     */
    public function calculatePanchanga(Request $request): JsonResponse
    {
        $this->logger->info('Calculate chart endpoint accessed');
        $startTime = microtime(true);

        try {
            $params = json_decode($request->getContent(), true);
            $params['convert_from'] = 'gregorian';
            $params['convert_to'] = 'gregorian';
            if (!in_array('panchanga', $params['infolevel'])) {
                $params['infolevel'][] = 'panchanga';
            }
            $result = $this->chart->calculator($params);
            $this->logger->debug('Chart calculated successfully');

            $endTime = microtime(true);
            $duration = number_format($endTime - $startTime, 3);
            $createdAt = (new \DateTime())->format('Y-m-d H:i:s');

            $response = [
                'chart' => $result,
                'duration_of_response' => (float) $duration,
                'created_at' => $createdAt,
            ];

            $this->logger->info('Returning successful response', $response);

            return $this->json($response);
        } catch (\Exception $e) {
            $this->logger->error('An error occurred: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->json([
                'error' => 'An internal error occurred',
            ], 500);
        }
    }

    /**
     * @Route("/api/chart", name="calculate_gregorian_chart", methods={"POST"})
     */
    public function calculateGregorianChart(Request $request): JsonResponse
    {
        $this->logger->info('Calculate chart endpoint accessed');
        $startTime = microtime(true);

        try {
            $params = json_decode($request->getContent(), true);
            $params['convert_from'] = 'gregorian';
            $params['convert_to'] = 'gregorian';
            $result = $this->chart->calculator($params);
            $this->logger->debug('Chart calculated successfully');

            $endTime = microtime(true);
            $duration = number_format($endTime - $startTime, 3);
            $createdAt = (new \DateTime())->format('Y-m-d H:i:s');

            $response = [
                'chart' => $result,
                'duration_of_response' => (float) $duration,
                'created_at' => $createdAt,
            ];

            $this->logger->info('Returning successful response', $response);

            return $this->json($response);
        } catch (\Exception $e) {
            $this->logger->error('An error occurred: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->json([
                'error' => 'An internal error occurred',
            ], 500);
        }
    }

    /**
     * @Route("/api/jalali/chart", name="calculate_jalali_chart", methods={"POST"})
     */
    public function calculateJalaliChart(Request $request): JsonResponse
    {
        $this->logger->info('Calculate chart endpoint accessed');
        $startTime = microtime(true);

        try {
            $params = json_decode($request->getContent(), true);
            $params['convert_from'] = 'jalali';
            $params['convert_to'] = 'jalali';
            $result = $this->chart->calculator($params);
            $this->logger->debug('Chart calculated successfully');

            $endTime = microtime(true);
            $duration = number_format($endTime - $startTime, 3);
            $createdAt = (new \DateTime())->format('Y-m-d H:i:s');

            $response = [
                'chart' => $result,
                'duration_of_response' => (float) $duration,
                'created_at' => $createdAt,
            ];

            $this->logger->info('Returning successful response', $response);

            return $this->json($response);
        } catch (\Exception $e) {
            $this->logger->error('An error occurred: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->json([
                'error' => 'An internal error occurred',
            ], 500);
        }
    }

    /**
     * @Route("/api/hijri/chart", name="calculate_hijri_chart", methods={"POST"})
     */
    public function calculateHijriChart(Request $request): JsonResponse
    {
        $this->logger->info('Calculate chart endpoint accessed');
        $startTime = microtime(true);

        try {
            $params = json_decode($request->getContent(), true);
            $params['convert_from'] = 'hijri';
            $params['convert_to'] = 'hijri';
            $result = $this->chart->calculator($params);
            $this->logger->debug('Chart calculated successfully');

            $endTime = microtime(true);
            $duration = number_format($endTime - $startTime, 3);
            $createdAt = (new \DateTime())->format('Y-m-d H:i:s');

            $response = [
                'chart' => $result,
                'duration_of_response' => (float) $duration,
                'created_at' => $createdAt,
            ];

            $this->logger->info('Returning successful response', $response);

            return $this->json($response);
        } catch (\Exception $e) {
            $this->logger->error('An error occurred: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->json([
                'error' => 'An internal error occurred',
            ], 500);
        }
    }

    /**
     * @Route("/api/now", name="get_now_chart", methods={"GET"})
     */
    public function getNowChart(Request $request): JsonResponse
    {
        $this->logger->info('Get now chart endpoint accessed');
        $startTime = microtime(true);

        try {
            $timezone = $request->query->get('timezone', 'Asia/Tehran');
            $latitude = $request->query->get('latitude', '35.708309');
            $longitude = $request->query->get('longitude', '51.380730');

            $params = [
                'infolevel' => [],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'time_zone' => $timezone,
            ];

            $params['infolevel'] = [];


            $result = $this->chart->calculator($params);
            $this->logger->debug('Chart calculated successfully');

            $endTime = microtime(true);
            $duration = number_format($endTime - $startTime, 3);
            $createdAt = (new \DateTime())->format('Y-m-d H:i:s');

            $response = [
                'chart' => $result,
                'duration_of_response' => (float) $duration,
                'created_at' => $createdAt,
            ];

            $this->logger->info('Returning successful response', $response);

            return $this->json($response);
        } catch (\Exception $e) {
            $this->logger->error('An error occurred: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->json([
                'error' => 'An internal error occurred',
            ], 500);
        }
    }
}