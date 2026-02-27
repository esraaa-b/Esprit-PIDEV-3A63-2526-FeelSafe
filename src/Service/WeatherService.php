<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class WeatherService
{
    private const API_URL = 'https://api.openweathermap.org/data/2.5/weather';
    private const FORECAST_URL = 'https://api.openweathermap.org/data/2.5/forecast';
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private string $apiKey,
        private string $defaultCity = 'Tunis'
    ) {
    }

    /**
     * Récupère la météo actuelle pour une ville
     */
    public function getCurrentWeather(?string $city = null): ?array
    {
        $city = $city ?? $this->defaultCity;
        
        // Cache pour 30 minutes
        return $this->cache->get(
            'weather_current_' . strtolower($city),
            function (ItemInterface $item) use ($city) {
                $item->expiresAfter(1800); // 30 minutes

                try {
                    $response = $this->httpClient->request('GET', self::API_URL, [
                        'query' => [
                            'q' => $city,
                            'appid' => $this->apiKey,
                            'units' => 'metric',
                            'lang' => 'fr'
                        ]
                    ]);

                    if ($response->getStatusCode() === 200) {
                        $data = $response->toArray();
                        return $this->formatWeatherData($data);
                    }
                } catch (\Exception $e) {
                    // En cas d'erreur, retourner des données par défaut
                    return $this->getDefaultWeather();
                }

                return null;
            }
        );
    }

    /**
     * Récupère les prévisions pour les prochaines heures
     */
    public function getForecast(?string $city = null, int $hours = 12): array
    {
        $city = $city ?? $this->defaultCity;
        
        return $this->cache->get(
            'weather_forecast_' . strtolower($city) . '_' . $hours,
            function (ItemInterface $item) use ($city, $hours) {
                $item->expiresAfter(3600); // 1 heure

                try {
                    $response = $this->httpClient->request('GET', self::FORECAST_URL, [
                        'query' => [
                            'q' => $city,
                            'appid' => $this->apiKey,
                            'units' => 'metric',
                            'lang' => 'fr'
                        ]
                    ]);

                    if ($response->getStatusCode() === 200) {
                        $data = $response->toArray();
                        return $this->formatForecastData($data, $hours);
                    }
                } catch (\Exception $e) {
                    return [];
                }

                return [];
            }
        );
    }

    /**
     * Formate les données météo
     */
    private function formatWeatherData(array $data): array
    {
        return [
            'temperature' => round($data['main']['temp']),
            'feels_like' => round($data['main']['feels_like']),
            'humidity' => $data['main']['humidity'],
            'description' => ucfirst($data['weather'][0]['description']),
            'icon' => $data['weather'][0]['icon'],
            'wind_speed' => round($data['wind']['speed'] * 3.6), // m/s to km/h
            'condition' => $data['weather'][0]['main'],
            'city' => $data['name'],
            'country' => $data['sys']['country'],
        ];
    }

    /**
     * Formate les prévisions
     */
    private function formatForecastData(array $data, int $hours): array
    {
        $forecast = [];
        $count = min($hours / 3, count($data['list'])); // API retourne par 3h

        for ($i = 0; $i < $count; $i++) {
            $item = $data['list'][$i];
            $forecast[] = [
                'time' => date('H:i', $item['dt']),
                'temperature' => round($item['main']['temp']),
                'description' => ucfirst($item['weather'][0]['description']),
                'icon' => $item['weather'][0]['icon'],
                'condition' => $item['weather'][0]['main'],
            ];
        }

        return $forecast;
    }

    /**
     * Détermine si c'est un bon moment pour une activité extérieure
     */
    public function isGoodForOutdoorActivity(?string $city = null): bool
    {
        $weather = $this->getCurrentWeather($city);
        
        if (!$weather) {
            return true; // Par défaut, on suppose que oui
        }

        // Critères pour activité extérieure
        $goodConditions = ['Clear', 'Clouds'];
        $goodTemperature = $weather['temperature'] >= 10 && $weather['temperature'] <= 35;
        $lowWind = $weather['wind_speed'] < 30; // km/h

        return in_array($weather['condition'], $goodConditions) 
            && $goodTemperature 
            && $lowWind;
    }

    /**
     * Retourne une catégorie météo pour les recommandations
     */
    public function getWeatherCategory(?string $city = null): string
    {
        $weather = $this->getCurrentWeather($city);
        
        if (!$weather) {
            return 'neutral';
        }

        $condition = $weather['condition'];
        $temperature = $weather['temperature'];

        // Catégorisation
        if (in_array($condition, ['Rain', 'Drizzle', 'Thunderstorm', 'Snow'])) {
            return 'indoor'; // Temps mauvais → activités intérieures
        }

        if ($temperature < 10 || $temperature > 35) {
            return 'indoor'; // Température extrême → intérieur
        }

        if (in_array($condition, ['Clear']) && $temperature >= 15 && $temperature <= 28) {
            return 'outdoor'; // Temps idéal → extérieur
        }

        return 'neutral'; // Temps OK → pas de préférence
    }

    /**
     * Données par défaut en cas d'erreur API
     */
    private function getDefaultWeather(): array
    {
        return [
            'temperature' => 20,
            'feels_like' => 20,
            'humidity' => 50,
            'description' => 'Partiellement nuageux',
            'icon' => '02d',
            'wind_speed' => 10,
            'condition' => 'Clouds',
            'city' => $this->defaultCity,
            'country' => 'TN',
        ];
    }

    /**
     * Récupère l'emoji correspondant à la météo
     */
    public function getWeatherEmoji(?string $city = null): string
    {
        $weather = $this->getCurrentWeather($city);
        
        if (!$weather) {
            return '☁️';
        }

        return match ($weather['condition']) {
            'Clear' => '☀️',
            'Clouds' => '☁️',
            'Rain' => '🌧️',
            'Drizzle' => '🌦️',
            'Thunderstorm' => '⛈️',
            'Snow' => '❄️',
            'Mist', 'Fog' => '🌫️',
            default => '🌤️',
        };
    }

    /**
     * Recommandation textuelle basée sur la météo
     */
    public function getWeatherRecommendation(?string $city = null): string
    {
        $weather = $this->getCurrentWeather($city);
        
        if (!$weather) {
            return 'Prenez soin de vous aujourd\'hui !';
        }

        $category = $this->getWeatherCategory($city);
        $temp = $weather['temperature'];

        if ($category === 'outdoor') {
            return "Profitez du beau temps ({$temp}°C) pour une activité en extérieur ! 🌞";
        }

        if ($category === 'indoor') {
            if (in_array($weather['condition'], ['Rain', 'Drizzle', 'Thunderstorm'])) {
                return "Temps pluvieux dehors, parfait pour une session de méditation au chaud ! ☕";
            }
            return "Restez au chaud avec une activité relaxante à l'intérieur ! 🏠";
        }

        return "Temps agréable pour toutes vos activités de bien-être ! 😊";
    }
}