<?php namespace Cviebrock\EloquentTaggable\Test;

use Cviebrock\EloquentTaggable\Services\TagService;


/**
 * Class TagServiceTests
 */
class TagServiceTests extends TestCase
{

    /**
     * @var \Cviebrock\EloquentTaggable\Services\TagService
     */
    protected $service;

    /**
     * @var array
     */
    protected $testArray;

    /**
     * @var array
     */
    protected $testArrayNormalized;

    /**
     * @var string
     */
    protected $testString;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        // load the service
        $this->service = app(TagService::class);

        // helpers
        $this->testArray = ['Apple', 'Banana', 'Cherry'];
        $this->testArrayNormalized = ['apple', 'banana', 'cherry'];
        $this->testString = 'Apple,Banana,Cherry';
    }

    /**
     * Test the service was instantiated.
     */
    public function testServiceWasInstantiated()
    {
        $this->assertEquals(TagService::class, get_class($this->service));
    }

    /**
     * Test building a tag array from an array
     */
    public function testBuildTagArrayFromArray()
    {
        $tags = $this->service->buildTagArray($this->testArray);

        $this->assertArrayValuesAreEqual(
            $this->testArray,
            $tags
        );
    }

    /**
     * Test building a tag array from an object, which should
     * throw an exception.
     */
    public function testBuildTagArrayFromObject()
    {
        $object = new \stdClass;

        $this->expectException(\ErrorException::class);

        $this->service->buildTagArray($object);
    }

    /**
     * Test building a tag array from a Collection
     */
    public function testBuildTagArrayFromCollection()
    {
        $tags = $this->service->buildTagArray(collect($this->testArray));

        $this->assertArrayValuesAreEqual(
            $this->testArray,
            $tags
        );
    }

    /**
     * Test building a tag array from a string
     */
    public function testBuildTagArrayFromString()
    {
        $tags = $this->service->buildTagArray($this->testString);

        $this->assertArrayValuesAreEqual(
            $this->testArray,
            $tags
        );
    }

    /**
     * Test building a tag array from an array
     */
    public function testBuildNormalizedTagArrayFromArray()
    {
        $tags = $this->service->buildTagArrayNormalized($this->testArray);

        $this->assertArrayValuesAreEqual(
            $this->testArrayNormalized,
            $tags
        );
    }

    /**
     * Test building a tag array from a Collection
     */
    public function testBuildNormalizedTagArrayFromCollection()
    {
        $tags = $this->service->buildTagArrayNormalized(collect($this->testArray));

        $this->assertArrayValuesAreEqual(
            $this->testArrayNormalized,
            $tags
        );
    }

    /**
     * Test building a tag array from a string
     */
    public function testBuildNormalizedTagArrayFromString()
    {
        $tags = $this->service->buildTagArrayNormalized($this->testString);

        $this->assertArrayValuesAreEqual(
            $this->testArrayNormalized,
            $tags
        );
    }

    /**
     * Test getting the tag model keys from an array
     * of normalized tag names.
     */
    public function testGettingTagModelKeys()
    {
        // Create a model and generate some Tags
        $model = $this->newModel();
        $model->tag('Apple');
        $model->tag('Banana');
        $model->tag('Cherry');

        $keys = $this->service->getTagModelKeys(['apple', 'cherry']);

        $this->assertArrayValuesAreEqual(
            [1, 3],
            $keys
        );
    }

    /**
     * Test getting the tag model keys from an empty array.
     */
    public function testGettingTagModelKeysFromEmptyArray()
    {
        $keys = $this->service->getTagModelKeys();

        $this->assertEmpty($keys);
    }

    /**
     * Test getting all tag models.
     */
    public function testGettingAllTags()
    {
        // Create a model and generate some Tags
        $model = $this->newModel();
        $model->tag('Apple');
        $model->tag('Banana');
        $model->tag('Cherry');

        // Add a dummy model as well and tag it
        $dummy = $this->newDummy();
        $dummy->tag('Apple');
        $dummy->tag('Durian');

        // check the test model
        $allTags = $this->service->getAllTagsArray(TestModel::class);

        $this->assertCount(3, $allTags);
        $this->assertArrayValuesAreEqual(
            $this->testArray,
            $allTags
        );

        $allTagsNormalized = $this->service->getAllTagsArrayNormalized(TestModel::class);
        $this->assertCount(3, $allTagsNormalized);
        $this->assertArrayValuesAreEqual(
            $this->testArrayNormalized,
            $allTagsNormalized
        );

        // check the dummy model
        $allTags = $this->service->getAllTagsArray($dummy);

        $this->assertCount(2, $allTags);
        $this->assertArrayValuesAreEqual(
            ['Apple', 'Durian'],
            $allTags
        );

        $allTagsNormalized = $this->service->getAllTagsArrayNormalized($dummy);
        $this->assertCount(2, $allTagsNormalized);
        $this->assertArrayValuesAreEqual(
            ['apple', 'durian'],
            $allTagsNormalized
        );

        // check all models
        $allTags = $this->service->getAllTagsArray();

        $this->assertCount(4, $allTags);
        $this->assertArrayValuesAreEqual(
            ['Apple', 'Banana', 'Cherry', 'Durian'],
            $allTags
        );

        $allTagsNormalized = $this->service->getAllTagsArrayNormalized();
        $this->assertCount(4, $allTagsNormalized);
        $this->assertArrayValuesAreEqual(
            ['apple', 'banana', 'cherry', 'durian'],
            $allTagsNormalized
        );
    }

    /**
     * Test finding all unused tags.
     */
    public function testGettingAllUnusedTags()
    {
        // Create a model and generate some tags
        $model = $this->newModel();
        $model->tag('Apple');
        $model->tag('Banana');
        $model->tag('Cherry');

        // remove some
        $model->untag(['Apple', 'Banana']);

        $unusedTags = $this->service->getAllUnusedTags();

        $this->assertCount(2, $unusedTags);
        $this->assertArrayValuesAreEqual(
            ['Apple', 'Banana'],
            $unusedTags->pluck('name')->toArray()
        );
    }

    /**
     * Test renaming a tag.
     */
    public function testRenamingTag()
    {
        // Create a model and generate some tags
        $model = $this->newModel();
        $model->tag('Apple');
        $model->tag('Banana');
        $model->tag('Cherry');

        // Add a dummy model as well and tag it
        $dummy = $this->newDummy();
        $dummy->tag('Apple');
        $dummy->tag('Durian');

        // Rename the tags just for one model class
        $count = $this->service->renameTags('Apple', 'Apricot', TestModel::class);

        $this->assertEquals(1, $count);

        // Check the test model's tags were renamed
        $model->load('tags');
        $testTags = $model->getTagArrayAttribute();

        $this->assertCount(3, $testTags);
        $this->assertArrayValuesAreEqual(
            ['Apricot', 'Banana', 'Cherry'],
            $testTags
        );

        // Check the dummy model's tags were not renamed
        $dummy->load('tags');
        $dummyTags = $dummy->getTagArrayAttribute();

        $this->assertCount(2, $dummyTags);
        $this->assertArrayValuesAreEqual(
            ['Apple', 'Durian'],
            $dummyTags
        );

        // Confirm the list of all tags
        $allTags = $this->service->getAllTagsArray();

        $this->assertCount(5, $allTags);
        $this->assertArrayValuesAreEqual(
            ['Apricot', 'Apple', 'Banana', 'Cherry', 'Durian'],
            $allTags
        );
    }

    /**
     * Test renaming a tag across all models.
     */
    public function testRenamingTagAllModels()
    {
        // Create a model and generate some Tags
        $model = $this->newModel();
        $model->tag('Apple');
        $model->tag('Banana');
        $model->tag('Cherry');

        // Add a dummy model as well and tag it
        $dummy = $this->newDummy();
        $dummy->tag('Apple');
        $dummy->tag('Durian');

        // Rename the tags just for all model classes
        $count = $this->service->renameTags('Apple', 'Apricot');

        $this->assertEquals(1, $count);

        // Check the test model's tags were renamed
        $model->load('tags');
        $testTags = $model->getTagArrayAttribute();

        $this->assertCount(3, $testTags);
        $this->assertArrayValuesAreEqual(
            ['Apricot', 'Banana', 'Cherry'],
            $testTags
        );

        // Check the dummy model's tags were renamed
        $dummy->load('tags');
        $dummyTags = $dummy->getTagArrayAttribute();

        $this->assertCount(2, $dummyTags);
        $this->assertArrayValuesAreEqual(
            ['Apricot', 'Durian'],
            $dummyTags
        );

        // Confirm the list of all tags
        $allTags = $this->service->getAllTagsArray();

        $this->assertCount(4, $allTags);
        $this->assertArrayValuesAreEqual(
            ['Apricot', 'Banana', 'Cherry', 'Durian'],
            $allTags
        );
    }

    /**
     * Test renaming a non-existent tag.
     */
    public function testRenamingNonExistingTag()
    {
        // Create a model and generate some Tags
        $model = $this->newModel();
        $model->tag('Apple');
        $model->tag('Banana');
        $model->tag('Cherry');

        // Rename the tags just for one model class
        $count = $this->service->renameTags('Durian', 'Date', TestModel::class);

        $this->assertEquals(0, $count);
    }
}
