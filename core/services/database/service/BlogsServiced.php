<?php

namespace EventSmart\Multisite\core\services\database\service;

use EventEspresso\core\services\database\WordPressOption;
use EventEspresso\core\services\json\JsonDataHandler;
use EventEspresso\core\services\json\JsonDataWordpressOption;

/**
 * Class BlogsServiced
 * DAO (data access object) for managing data during blog service batch jobs
 *
 * @author  Brent Christensen
 * @package EventSmart\Multisite
 * @since   $VID:$
 */
class BlogsServiced extends JsonDataWordpressOption
{
    /**
     * default value used if nothing else is supplied
     */
    public const DATA_NOT_SET = [];

    /**
     * indicates that the blog in question has not yet been assessed
     * meaning queries will need to be run to determine if service is required
     */
    public const STATUS_NOT_ASSESSED = -1;

    /**
     * indicates that the blog in question has been assessed and service is required
     */
    public const STATUS_NEEDS_SERVICING = 0;

    /**
     * indicates that the blog in question has been assessed AND serviced
     */
    public const STATUS_SERVICE_COMPLETE = 1;

    /**
     * indicates that the blog in question has been assessed but no service is required
     */
    public const STATUS_OK_AS_IS = 2;


    /**
     * @var array
     */
    private $blogs = [];


    /**
     * @param string $option_name
     */
    public function __construct(string $option_name)
    {
        $json_data_handler = new JsonDataHandler();
        $json_data_handler->configure(JsonDataHandler::DATA_TYPE_ARRAY, JSON_OBJECT_AS_ARRAY);
        parent::__construct($json_data_handler, $option_name, BlogsServiced::DATA_NOT_SET, false);
    }


    /**
     * if this option has never been set before,
     * then create and save a new array using $blog_IDs as keys
     * and set ALL values to BlogsServiced::STATUS_NOT_ASSESSED
     *
     * @param array $blog_IDs
     * @return int
     */
    public function initialize(array $blog_IDs): int
    {
        if (! $this->optionExists()) {
            $blogs = [];
            foreach ($blog_IDs as $blog_ID) {
                if (! isset($blogs[ $blog_ID ])) {
                    $blogs[ $blog_ID ] = [
                        'id'     => $blog_ID,
                        'data'   => BlogsServiced::DATA_NOT_SET,
                        'status' => BlogsServiced::STATUS_NOT_ASSESSED,
                    ];
                }
            }
            ksort($blogs, SORT_NUMERIC);
            return $this->updateOption($blogs);
        }
        return WordPressOption::UPDATE_NONE;
    }


    /**
     * this is primarily used to set the blogs array's internal pointer
     *
     * @param int $blog_ID
     * @return array
     */
    public function findBlog(int $blog_ID): array
    {
        $this->getBlogs();
        reset($this->blogs);
        while (key($this->blogs) !== $blog_ID && key($this->blogs) !== null) {
            next($this->blogs);
        }
        if (key($this->blogs) === null) {
            end($this->blogs);
        }
        return current($this->blogs);
    }


    /**
     * returns array of blogs found with a status of STATUS_NEEDS_SERVICING
     *
     * @return array
     */
    public function findBlogsThatNeedServicing(): array
    {
        $this->getBlogs();
        $blogs_that_need_servicing = array_filter(
            $this->blogs,
            function ($blog) {
                return isset($blog['status']) && $blog['status'] === BlogsServiced::STATUS_NEEDS_SERVICING;
            }
        );
        // make sure internal pointer is set to first element
        reset($blogs_that_need_servicing);
        return $blogs_that_need_servicing;
    }


    /**
     * returns the ID for the next blog found with a status of STATUS_NOT_ASSESSED
     *
     * @return int
     */
    public function findNextBlogToAssess(): int
    {
        return $this->findNextBlogWithStatus(BlogsServiced::STATUS_NOT_ASSESSED);
    }


    /**
     * returns the ID for the next blog found with a status of STATUS_NOT_ASSESSED
     *
     * @return int
     */
    public function findNextBlogToService(): int
    {
        return $this->findNextBlogWithStatus(BlogsServiced::STATUS_NEEDS_SERVICING);
    }


    /**
     * returns the ID for the next blog found with a status matching the supplied value
     *
     * @param string $status
     * @return int
     */
    public function findNextBlogWithStatus(string $status): int
    {
        $this->getBlogs();
        // search the 'status' column for each blog till we find one with a value matching the supplied $status
        $blog_ID = array_search($status, array_column($this->blogs, 'status'));
        // set internal array pointer
        $blog = $this->findBlog($blog_ID);
        // returning blog ID this way instead of $blog_ID helps ensure that things are all valid
        return $blog['id'] ?? 0;
    }


    /**
     * @return int
     */
    public function firstBlogID(): int
    {
        $this->getBlogs();
        reset($this->blogs);
        if (key($this->blogs) === null) {
            return 0;
        }
        return key($this->blogs);
    }


    /**
     * @param int $blog_ID
     * @return array
     */
    public function getBlogData(int $blog_ID): array
    {
        return (array) $this->blogs[ $blog_ID ]['data'] ?? BlogsServiced::DATA_NOT_SET;
    }


    /**
     * @return void
     */
    private function getBlogs()
    {
        if (empty($this->blogs)) {
            $blogs       = $this->getAll();
            $this->blogs = $blogs;
        }
    }


    /**
     * @param int $blog_ID
     * @return bool
     */
    public function hasBeenAssessed(int $blog_ID): bool
    {
        $this->getBlogs();
        return isset($this->blogs[ $blog_ID ]['status'])
               && $this->blogs[ $blog_ID ]['status'] !== BlogsServiced::STATUS_NOT_ASSESSED;
    }


    /**
     * @return int
     */
    public function lastBlogID(): int
    {
        $this->getBlogs();
        return array_key_last($this->blogs) ?: 0;
    }


    /**
     * @param int $blog_ID
     * @return bool
     */
    public function servicingComplete(int $blog_ID): bool
    {
        $this->getBlogs();
        return isset($this->blogs[ $blog_ID ]['status'])
               && $this->blogs[ $blog_ID ]['status'] >= BlogsServiced::STATUS_SERVICE_COMPLETE;
    }


    /**
     * @param int $blog_ID
     * @return int one of the WordPressOption::UPDATE_* constants
     */
    public function requiresServicing(int $blog_ID): int
    {
        $this->getBlogs();
        $this->blogs[ $blog_ID ]['status'] = BlogsServiced::STATUS_NEEDS_SERVICING;
        return $this->updateOption($this->blogs);
    }


    /**
     * @return int
     */
    public function nextBlogID(): int
    {
        $this->getBlogs();
        next($this->blogs);
        if (key($this->blogs) === null) {
            end($this->blogs);
        }
        return key($this->blogs);
    }


    /**
     * @return int
     */
    public function prevBlogID(): int
    {
        $this->getBlogs();
        prev($this->blogs);
        if (key($this->blogs) === null) {
            reset($this->blogs);
        }
        return key($this->blogs);
    }


    /**
     * @param int $blog_ID
     * @return int one of the WordPressOption::UPDATE_* constants
     */
    public function servicingNotNeeded(int $blog_ID): int
    {
        $this->getBlogs();
        $this->blogs[ $blog_ID ]['status'] = BlogsServiced::STATUS_OK_AS_IS;
        return $this->updateOption($this->blogs);
    }


    /**
     * @param int $blog_ID
     * @return int one of the WordPressOption::UPDATE_* constants
     */
    public function serviceCompleted(int $blog_ID): int
    {
        $this->getBlogs();
        $this->blogs[ $blog_ID ]['status'] = BlogsServiced::STATUS_SERVICE_COMPLETE;
        return $this->updateOption($this->blogs);
    }


    /**
     * @param int   $blog_ID
     * @param array $data
     * @return int one of the WordPressOption::UPDATE_* constants
     */
    public function setBlogData(int $blog_ID, array $data): int
    {
        $this->getBlogs();
        $this->blogs[ $blog_ID ]['data'] = $data ?: BlogsServiced::DATA_NOT_SET;
        return $this->updateOption($this->blogs);
    }


    /**
     * @param int   $blog_ID
     * @param array $data
     * @return int one of the WordPressOption::UPDATE_* constants
     */
    public function addBlogData(int $blog_ID, array $data): int
    {
        $this->getBlogs();
        $this->blogs[ $blog_ID ]['data'] = $this->blogs[ $blog_ID ]['data'] ?? BlogsServiced::DATA_NOT_SET;
        $this->blogs[ $blog_ID ]['data'] = array_merge($this->blogs[ $blog_ID ]['data'], $data);
        return $this->updateOption($this->blogs);
    }
}
