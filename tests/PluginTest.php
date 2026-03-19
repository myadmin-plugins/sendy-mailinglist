<?php

declare(strict_types=1);

namespace Detain\MyAdminSendy\Tests;

use Detain\MyAdminSendy\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Unit tests for the Detain\MyAdminSendy\Plugin class.
 *
 * These tests verify class structure, static properties, hook registrations,
 * event handler signatures, and method behaviour without requiring database
 * or network access.
 */
class PluginTest extends TestCase
{
    /**
     * Cached ReflectionClass instance for the Plugin class.
     *
     * @var ReflectionClass<Plugin>
     */
    private static ReflectionClass $ref;

    /**
     * Initialise the reflection instance once for the whole suite.
     */
    public static function setUpBeforeClass(): void
    {
        self::$ref = new ReflectionClass(Plugin::class);
    }

    // ------------------------------------------------------------------
    //  Class existence and instantiation
    // ------------------------------------------------------------------

    /**
     * Test that the Plugin class exists and is loadable.
     */
    public function testPluginClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Test that the Plugin class can be instantiated.
     */
    public function testPluginCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the Plugin class is not abstract.
     */
    public function testPluginIsNotAbstract(): void
    {
        $this->assertFalse(self::$ref->isAbstract());
    }

    /**
     * Test that the Plugin class is not an interface.
     */
    public function testPluginIsNotInterface(): void
    {
        $this->assertFalse(self::$ref->isInterface());
    }

    /**
     * Test that the Plugin class lives in the correct namespace.
     */
    public function testPluginNamespace(): void
    {
        $this->assertSame('Detain\\MyAdminSendy', self::$ref->getNamespaceName());
    }

    // ------------------------------------------------------------------
    //  Static property existence and values
    // ------------------------------------------------------------------

    /**
     * Test that the $name static property exists and is a non-empty string.
     */
    public function testNamePropertyExists(): void
    {
        $this->assertTrue(self::$ref->hasProperty('name'));
        $prop = self::$ref->getProperty('name');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertIsString(Plugin::$name);
        $this->assertNotEmpty(Plugin::$name);
    }

    /**
     * Test the value of the $name property.
     */
    public function testNamePropertyValue(): void
    {
        $this->assertSame('Sendy Plugin', Plugin::$name);
    }

    /**
     * Test that the $description static property exists and is a non-empty string.
     */
    public function testDescriptionPropertyExists(): void
    {
        $this->assertTrue(self::$ref->hasProperty('description'));
        $prop = self::$ref->getProperty('description');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
    }

    /**
     * Test that the $help static property exists and is a string.
     */
    public function testHelpPropertyExists(): void
    {
        $this->assertTrue(self::$ref->hasProperty('help'));
        $prop = self::$ref->getProperty('help');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertIsString(Plugin::$help);
    }

    /**
     * Test that the $type static property is "plugin".
     */
    public function testTypePropertyValue(): void
    {
        $this->assertTrue(self::$ref->hasProperty('type'));
        $this->assertSame('plugin', Plugin::$type);
    }

    // ------------------------------------------------------------------
    //  getHooks()
    // ------------------------------------------------------------------

    /**
     * Test that getHooks() is a public static method.
     */
    public function testGetHooksIsPublicStatic(): void
    {
        $method = self::$ref->getMethod('getHooks');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that getHooks() returns an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Test that getHooks() contains the system.settings hook.
     */
    public function testGetHooksContainsSystemSettings(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('system.settings', $hooks);
        $this->assertSame([Plugin::class, 'getSettings'], $hooks['system.settings']);
    }

    /**
     * Test that getHooks() contains the account.activated hook.
     */
    public function testGetHooksContainsAccountActivated(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('account.activated', $hooks);
        $this->assertSame([Plugin::class, 'doAccountActivated'], $hooks['account.activated']);
    }

    /**
     * Test that getHooks() contains the mailinglist.subscribe hook.
     */
    public function testGetHooksContainsMailinglistSubscribe(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('mailinglist.subscribe', $hooks);
        $this->assertSame([Plugin::class, 'doMailinglistSubscribe'], $hooks['mailinglist.subscribe']);
    }

    /**
     * Test that every hook callback references a callable method on Plugin.
     */
    public function testAllHookCallbacksAreCallable(): void
    {
        foreach (Plugin::getHooks() as $eventName => $callback) {
            $this->assertIsArray($callback, "Hook '{$eventName}' callback must be an array");
            $this->assertCount(2, $callback, "Hook '{$eventName}' callback must have two elements");
            $this->assertSame(Plugin::class, $callback[0], "Hook '{$eventName}' must reference Plugin class");
            $this->assertTrue(
                self::$ref->hasMethod($callback[1]),
                "Hook '{$eventName}' references non-existent method '{$callback[1]}'"
            );
        }
    }

    /**
     * Test that all hook callbacks are public static methods.
     */
    public function testAllHookCallbacksArePublicStatic(): void
    {
        foreach (Plugin::getHooks() as $eventName => $callback) {
            $method = self::$ref->getMethod($callback[1]);
            $this->assertTrue(
                $method->isPublic(),
                "Handler for '{$eventName}' must be public"
            );
            $this->assertTrue(
                $method->isStatic(),
                "Handler for '{$eventName}' must be static"
            );
        }
    }

    // ------------------------------------------------------------------
    //  Event handler method signatures
    // ------------------------------------------------------------------

    /**
     * Test that doAccountActivated accepts a GenericEvent parameter.
     */
    public function testDoAccountActivatedSignature(): void
    {
        $method = self::$ref->getMethod('doAccountActivated');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that doMailinglistSubscribe accepts a GenericEvent parameter.
     */
    public function testDoMailinglistSubscribeSignature(): void
    {
        $method = self::$ref->getMethod('doMailinglistSubscribe');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that getSettings accepts a GenericEvent parameter.
     */
    public function testGetSettingsSignature(): void
    {
        $method = self::$ref->getMethod('getSettings');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    // ------------------------------------------------------------------
    //  doSetup() and doEmailSetup() method structure
    // ------------------------------------------------------------------

    /**
     * Test that doSetup is a public static method accepting one parameter.
     */
    public function testDoSetupMethodStructure(): void
    {
        $method = self::$ref->getMethod('doSetup');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertCount(1, $method->getParameters());
        $this->assertSame('accountId', $method->getParameters()[0]->getName());
    }

    /**
     * Test that doEmailSetup is a public static method accepting two parameters.
     */
    public function testDoEmailSetupMethodStructure(): void
    {
        $method = self::$ref->getMethod('doEmailSetup');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('email', $params[0]->getName());
        $this->assertSame('params', $params[1]->getName());
        $this->assertTrue($params[1]->isOptional());
    }

    /**
     * Test that doEmailSetup second parameter defaults to false.
     */
    public function testDoEmailSetupParamsDefaultValue(): void
    {
        $method = self::$ref->getMethod('doEmailSetup');
        $params = $method->getParameters();
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertFalse($params[1]->getDefaultValue());
    }

    // ------------------------------------------------------------------
    //  Constructor
    // ------------------------------------------------------------------

    /**
     * Test that the constructor exists and is public.
     */
    public function testConstructorIsPublic(): void
    {
        $constructor = self::$ref->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPublic());
    }

    /**
     * Test that the constructor has no required parameters.
     */
    public function testConstructorHasNoParameters(): void
    {
        $constructor = self::$ref->getConstructor();
        $this->assertCount(0, $constructor->getParameters());
    }

    // ------------------------------------------------------------------
    //  getSettings() — integration via anonymous settings stub
    // ------------------------------------------------------------------

    /**
     * Test that getSettings calls expected settings methods on the subject.
     *
     * Uses an anonymous class to avoid mocking vendor classes.
     */
    public function testGetSettingsRegistersExpectedSettings(): void
    {
        $calls = [];
        $settings = new class($calls) {
            /** @var array<int, array{method: string, args: array}> */
            private array $callLog;

            /**
             * @param array<int, array{method: string, args: array}> &$calls
             */
            public function __construct(array &$calls)
            {
                $this->callLog = &$calls;
            }

            /**
             * @param mixed ...$args
             */
            public function add_dropdown_setting(...$args): void
            {
                $this->callLog[] = ['method' => 'add_dropdown_setting', 'args' => $args];
            }

            /**
             * @param mixed ...$args
             */
            public function add_password_setting(...$args): void
            {
                $this->callLog[] = ['method' => 'add_password_setting', 'args' => $args];
            }

            /**
             * @param mixed ...$args
             */
            public function add_text_setting(...$args): void
            {
                $this->callLog[] = ['method' => 'add_text_setting', 'args' => $args];
            }
        };

        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        $this->assertCount(4, $calls, 'getSettings should register exactly 4 settings');

        $methods = array_column($calls, 'method');
        $this->assertSame('add_dropdown_setting', $methods[0]);
        $this->assertSame('add_password_setting', $methods[1]);
        $this->assertSame('add_text_setting', $methods[2]);
        $this->assertSame('add_text_setting', $methods[3]);
    }

    /**
     * Test that getSettings registers the sendy_enable setting key.
     */
    public function testGetSettingsRegistersEnableSetting(): void
    {
        $calls = [];
        $settings = new class($calls) {
            /** @var array<int, array> */
            private array $callLog;

            /** @param array &$calls */
            public function __construct(array &$calls)
            {
                $this->callLog = &$calls;
            }

            /** @param mixed ...$args */
            public function add_dropdown_setting(...$args): void
            {
                $this->callLog[] = $args;
            }

            /** @param mixed ...$args */
            public function add_password_setting(...$args): void
            {
                $this->callLog[] = $args;
            }

            /** @param mixed ...$args */
            public function add_text_setting(...$args): void
            {
                $this->callLog[] = $args;
            }
        };

        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        // First call is add_dropdown_setting for sendy_enable
        $this->assertSame('sendy_enable', $calls[0][2]);
    }

    /**
     * Test that getSettings registers the sendy_api_key setting key.
     */
    public function testGetSettingsRegistersApiKeySetting(): void
    {
        $calls = [];
        $settings = new class($calls) {
            /** @var array<int, array> */
            private array $callLog;

            /** @param array &$calls */
            public function __construct(array &$calls)
            {
                $this->callLog = &$calls;
            }

            /** @param mixed ...$args */
            public function add_dropdown_setting(...$args): void
            {
                $this->callLog[] = $args;
            }

            /** @param mixed ...$args */
            public function add_password_setting(...$args): void
            {
                $this->callLog[] = $args;
            }

            /** @param mixed ...$args */
            public function add_text_setting(...$args): void
            {
                $this->callLog[] = $args;
            }
        };

        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        // Second call is add_password_setting for sendy_api_key
        $this->assertSame('sendy_api_key', $calls[1][2]);
    }

    /**
     * Test that getSettings registers the sendy_list_id setting key.
     */
    public function testGetSettingsRegistersListIdSetting(): void
    {
        $calls = [];
        $settings = new class($calls) {
            /** @var array<int, array> */
            private array $callLog;

            /** @param array &$calls */
            public function __construct(array &$calls)
            {
                $this->callLog = &$calls;
            }

            /** @param mixed ...$args */
            public function add_dropdown_setting(...$args): void
            {
                $this->callLog[] = $args;
            }

            /** @param mixed ...$args */
            public function add_password_setting(...$args): void
            {
                $this->callLog[] = $args;
            }

            /** @param mixed ...$args */
            public function add_text_setting(...$args): void
            {
                $this->callLog[] = $args;
            }
        };

        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        // Third call is add_text_setting for sendy_list_id
        $this->assertSame('sendy_list_id', $calls[2][2]);
    }

    /**
     * Test that getSettings registers the sendy_apiurl setting key.
     */
    public function testGetSettingsRegistersApiUrlSetting(): void
    {
        $calls = [];
        $settings = new class($calls) {
            /** @var array<int, array> */
            private array $callLog;

            /** @param array &$calls */
            public function __construct(array &$calls)
            {
                $this->callLog = &$calls;
            }

            /** @param mixed ...$args */
            public function add_dropdown_setting(...$args): void
            {
                $this->callLog[] = $args;
            }

            /** @param mixed ...$args */
            public function add_password_setting(...$args): void
            {
                $this->callLog[] = $args;
            }

            /** @param mixed ...$args */
            public function add_text_setting(...$args): void
            {
                $this->callLog[] = $args;
            }
        };

        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        // Fourth call is add_text_setting for sendy_apiurl
        $this->assertSame('sendy_apiurl', $calls[3][2]);
    }

    // ------------------------------------------------------------------
    //  getSettings() — dropdown values
    // ------------------------------------------------------------------

    /**
     * Test that sendy_enable dropdown has correct option values.
     */
    public function testSendyEnableDropdownValues(): void
    {
        $calls = [];
        $settings = new class($calls) {
            /** @var array<int, array> */
            private array $callLog;

            /** @param array &$calls */
            public function __construct(array &$calls)
            {
                $this->callLog = &$calls;
            }

            /** @param mixed ...$args */
            public function add_dropdown_setting(...$args): void
            {
                $this->callLog[] = ['method' => 'add_dropdown_setting', 'args' => $args];
            }

            /** @param mixed ...$args */
            public function add_password_setting(...$args): void
            {
            }

            /** @param mixed ...$args */
            public function add_text_setting(...$args): void
            {
            }
        };

        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        // The dropdown values are the 7th argument (index 6)
        $this->assertSame(['0', '1'], $calls[0]['args'][6]);
        // The dropdown labels are the 8th argument (index 7)
        $this->assertSame(['No', 'Yes'], $calls[0]['args'][7]);
    }

    // ------------------------------------------------------------------
    //  Method count — ensure no unexpected public methods creep in
    // ------------------------------------------------------------------

    /**
     * Test the expected set of public methods on Plugin.
     */
    public function testExpectedPublicMethods(): void
    {
        $expected = [
            '__construct',
            'getHooks',
            'getSettings',
            'doAccountActivated',
            'doMailinglistSubscribe',
            'doSetup',
            'doEmailSetup',
        ];

        $actual = array_map(
            static fn (ReflectionMethod $m): string => $m->getName(),
            self::$ref->getMethods(ReflectionMethod::IS_PUBLIC)
        );

        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
    }

    // ------------------------------------------------------------------
    //  Hook event names — static analysis style checks
    // ------------------------------------------------------------------

    /**
     * Test that hook event names are non-empty strings with dot notation.
     */
    public function testHookEventNamesAreDotNotation(): void
    {
        foreach (array_keys(Plugin::getHooks()) as $eventName) {
            $this->assertIsString($eventName);
            $this->assertNotEmpty($eventName);
            $this->assertStringContainsString('.', $eventName, "Event name '{$eventName}' should use dot notation");
        }
    }

    // ------------------------------------------------------------------
    //  Static property types — static analysis
    // ------------------------------------------------------------------

    /**
     * @dataProvider staticPropertyProvider
     *
     * Test that each static property exists, is public, and is a string.
     *
     * @param string $propertyName
     */
    public function testStaticPropertiesArePublicStrings(string $propertyName): void
    {
        $this->assertTrue(self::$ref->hasProperty($propertyName));
        $prop = self::$ref->getProperty($propertyName);
        $this->assertTrue($prop->isPublic(), "{$propertyName} should be public");
        $this->assertTrue($prop->isStatic(), "{$propertyName} should be static");
        $this->assertIsString($prop->getValue(), "{$propertyName} should be a string");
    }

    /**
     * Data provider for static property tests.
     *
     * @return array<string, array{string}>
     */
    public static function staticPropertyProvider(): array
    {
        return [
            'name'        => ['name'],
            'description' => ['description'],
            'help'        => ['help'],
            'type'        => ['type'],
        ];
    }
}
