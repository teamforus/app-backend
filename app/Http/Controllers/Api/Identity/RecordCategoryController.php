<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Requests\Api\RecordCategories\RecordCategoryStoreRequest;
use App\Http\Requests\Api\RecordCategories\RecordCategoryUpdateRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RecordCategoryController extends Controller
{
    private $recordRepo;

    /**
     * RecordCategoryController constructor.
     */
    public function __construct() {
        $this->recordRepo = resolve('forus.services.record');
    }

    /**
     * Get list categories
     * @return array
     */
    public function index()
    {
        return $this->recordRepo->categoriesList(
            auth_address()
        );
    }

    /**
     * Create new record category
     * @param RecordCategoryStoreRequest $request
     * @return array
     */
    public function store(
        RecordCategoryStoreRequest $request
    ) {
        $success = !!$this->recordRepo->categoryCreate(
            auth_address(),
            $request->get('name'),
            $request->input('order', 0)
        );

        return compact('success');
    }

    /**
     * Get record category
     * @param int $recordCategoryId
     * @return array|null
     */
    public function show(
        int $recordCategoryId
    ) {
        $identity = auth_address();

        if (empty($this->recordRepo->categoryGet(
            $identity, $recordCategoryId
        ))) {
            abort(404, trans('record-categories.codes.404'));
        }

        $category = $this->recordRepo->categoryGet(
            $identity,
            $recordCategoryId
        );

        if (!$category) {
            abort(404, trans('record-categories.codes.404'));
        }

        return $category;
    }

    /**
     * Update record category
     * @param RecordCategoryUpdateRequest $request
     * @param int $recordCategoryId
     * @return array
     */
    public function update(
        RecordCategoryUpdateRequest $request,
        int $recordCategoryId
    ) {
        $identity = auth_address();

        if (empty($this->recordRepo->categoryGet(
            $identity, $recordCategoryId
        ))) {
            abort(404, trans('record-categories.codes.404'));
        }

        $success = $this->recordRepo->categoryUpdate(
            auth_address(),
            $recordCategoryId,
            $request->input('name', null),
            $request->input('order', null)
        );

        return compact('success');
    }

    /**
     * Delete record category
     * @param int $recordCategoryId
     * @return array
     * @throws \Exception
     */
    public function destroy(
        int $recordCategoryId
    ) {
        $identity = auth_address();

        if (empty($this->recordRepo->categoryGet(
            $identity, $recordCategoryId
        ))) {
            abort(404, trans('record-categories.codes.404'));
        }

        $success = $this->recordRepo->categoryDelete(
            auth_address(),
            $recordCategoryId
        );

        return compact('success');
    }

    /**
     * Sort record categories
     * @param Request $request
     * @return array
     */
    public function sort(
        Request $request
    ) {
        $this->recordRepo->categoriesSort(
            auth_address(),
            collect($request->get('categories', []))->toArray()
        );

        $success = true;

        return compact('success');
    }
}
