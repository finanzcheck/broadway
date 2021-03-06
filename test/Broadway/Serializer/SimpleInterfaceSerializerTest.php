<?php

/*
 * This file is part of the broadway/broadway package.
 *
 * (c) Qandidate.com <opensource@qandidate.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Broadway\Serializer;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SimpleInterfaceSerializerTest extends TestCase
{
    /**
     * @var SimpleInterfaceSerializer
     */
    private $serializer;

    protected function setUp()
    {
        $this->serializer = new SimpleInterfaceSerializer();
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_an_object_does_not_implement_Serializable()
    {
        $this->expectException(SerializationException::class);
        $this->expectExceptionMessage(sprintf(
            'Object \'%s\' does not implement %s',
            \stdClass::class,
            Serializable::class
        ));

        $this->serializer->serialize(new \stdClass());
    }

    /**
     * @test
     *
     * @todo custom exception
     */
    public function it_throws_an_exception_if_class_not_set_in_data()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Key \'class\' should be set');

        $this->serializer->deserialize(['payload' => []]);
    }

    /**
     * @test
     *
     * @todo custom exception
     */
    public function it_throws_an_exception_if_payload_not_set_in_data()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Key \'payload\' should be set');

        $this->serializer->deserialize(['class' => 'SomeClass']);
    }

    /**
     * @test
     */
    public function it_serializes_objects_implementing_Serializable()
    {
        $object = new TestSerializable('bar');

        $this->assertEquals([
            'class' => 'Broadway\Serializer\TestSerializable',
            'payload' => ['foo' => 'bar'],
        ], $this->serializer->serialize($object));
    }

    /**
     * @test
     */
    public function it_deserializes_classes_implementing_Serializable()
    {
        $data = ['class' => 'Broadway\Serializer\TestSerializable', 'payload' => ['foo' => 'bar']];

        $this->assertEquals(new TestSerializable('bar'), $this->serializer->deserialize($data));
    }

    /**
     * @test
     */
    public function it_can_deserialize_classes_it_has_serialized()
    {
        $object = new TestSerializable('bar');

        $serialized = $this->serializer->serialize($object);
        $deserialized = $this->serializer->deserialize($serialized);

        $this->assertEquals($object, $deserialized);
    }

    /**
     * @test
     * @dataProvider serializable_test_data
     */
    public function it_serializes($data)
    {
        $object = new TestSerializable($data);

        $serialized = $this->serializer->serialize($object);

        $deserialized = $this->serializer->deserialize($serialized);

        $this->assertInstanceOf(TestSerializable::class, $deserialized);
        $this->assertEquals($object, $deserialized);
    }

    /**
     * @test
     * @dataProvider serializable_test_data
     */
    public function it_fails_to_serialize($data)
    {
        $this->expectException(SerializationException::class);

        $this->serializer->serialize($data);
    }

    public function serializable_test_data()
    {
        return [
            'null' => [null],
            'integer' => [0],
            'float' => [3.14],
            'string' => ['impossible'],
            'array' => [[]],
            'object' => [(object)[]],
            'bad array' => [['class' => 'foo', 'payload' => 'bar']],
        ];
    }
}

class TestSerializable implements Serializable
{
    private $foo;

    public function __construct($foo)
    {
        $this->foo = $foo;
    }

    /**
     * @return $this
     */
    public static function deserialize(array $data)
    {
        return new self($data['foo']);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): array
    {
        return ['foo' => $this->foo];
    }
}
