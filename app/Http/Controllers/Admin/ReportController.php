<?php

declare(strict_types=1);

/*
 * +----------------------------------------------------------------------+
 * |                          ThinkSNS Plus                               |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 2016-Present ZhiYiChuangXiang Technology Co., Ltd.     |
 * +----------------------------------------------------------------------+
 * | This source file is subject to enterprise private license, that is   |
 * | bundled with this package in the file LICENSE, and is available      |
 * | through the world-wide-web at the following url:                     |
 * | https://github.com/slimkit/plus/blob/master/LICENSE                  |
 * +----------------------------------------------------------------------+
 * | Author: Slim Kit Group <master@zhiyicx.com>                          |
 * | Homepage: www.thinksns.com                                           |
 * +----------------------------------------------------------------------+
 */

namespace Zhiyi\Plus\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Zhiyi\Plus\Http\Controllers\Controller;
use Zhiyi\Plus\Models\Report as ReportModel;

class ReportController extends Controller
{
    /**
     * 举报列表.
     *
     * @param  Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $pcInstalled = $this->getPcInstalled();

        $limit = $request->query('limit');
        $offset = $request->query('offset');

        $query = ReportModel::with(['user', 'target', 'reportable']);

        $count = $query->count();
        $items = $query->limit($limit)->offset($offset)->get();

        if ($pcInstalled) {
            $items->map(function ($item) {
                $item->view = route('pc:reportview', ['reportable_id' => $item->reportable_id, 'reportable_type' => $item->reportable_type]);

                return $item;
            });
        }

        return response()->json($items, 200, ['x-total' => $count]);
    }

    /**
     * 处理举报.
     *
     * @param  Request     $request
     * @param  ReportModel $report
     * @return mixed
     */
    public function deal(Request $request, ReportModel $report)
    {
        $mark = $request->input('mark');
        $report->status = 1;
        $report->mark = $mark;
        $report->save();

        if ($report->user) {
            $report->user->sendNotifyMessage('user-report:notice', '你的举报已被平台处理', [
                'report' => $report,
            ]);
        }

        if ($report->target) {
            $report->target->sendNotifyMessage(
                'user-report:notice',
                '你的'.$report->subject.'已被举报',
                ['report' => $report]
            );
        }

        return response()->json(['message' => ['操作成功']], 201);
    }

    /**
     * 驳回举报.
     *
     * @param Request $request
     * @param ReportModel $report
     * @return mixed
     * @author BS <414606094@qq.com>
     */
    public function reject(Request $request, ReportModel $report)
    {
        $mark = $request->input('mark');
        $report->status = 2;
        $report->mark = $mark;
        $report->save();

        if ($report->user) {
            $report->user->sendNotifyMessage('user-report:notice', '你的举报已被平台处理', [
                'report' => $report,
            ]);
        }

        return response()->json(['message' => ['操作成功']], 201);
    }

    /**
     * 判断是否安装了pc扩展.
     *
     * @return bool
     * @author BS <414606094@qq.com>
     */
    protected function getPcInstalled(): bool
    {
        return class_exists(\Zhiyi\Component\ZhiyiPlus\PlusComponentPc\PcServiceProvider::class);
    }
}
