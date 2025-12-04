<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     */
    public static function prepare(): void
    {
        // Only start ChromeDriver if we are configured to use it.
        if (env('DUSK_BROWSER', 'chrome') === 'chrome' && ! static::runningInSail()) {
            static::startChromeDriver();
        }
    }

    /**
     * Create the RemoteWebDriver instance based on the DUSK_BROWSER environment variable.
     */
    protected function driver(): RemoteWebDriver
    {
        // Use Safari if specified
        if (env('DUSK_BROWSER') === 'safari') {
            // The default URL for safaridriver is often http://localhost:4444
            // If you have issues, you might need to start it manually with `safaridriver -p 4444`
            return RemoteWebDriver::create(
                'http://localhost:4444',
                DesiredCapabilities::safari()
            );
        }

        // Default to Chrome (headless by default)
        $options = (new ChromeOptions())->addArguments(array_filter([
            '--disable-gpu',
            '--window-size=1920,1080',
            ! env('DUSK_HEADLESS_DISABLED') ? '--headless' : null,
        ]));

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            )
        );
    }
}
