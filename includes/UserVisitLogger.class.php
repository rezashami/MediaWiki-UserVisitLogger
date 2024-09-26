<?php
use MediaWiki\MediaWikiServices;

class UserVisitLogger
{
    public static function onLoadExtensionSchemaUpdates(DatabaseUpdater $updater)
    {
        $updater->addExtensionTable('user_visit_log', __DIR__ . '/sql/UserVisitLogger.sql');
    }
    /**
     * Hook: BeforePageDisplay
     * @param OutputPage $out
     * @param Skin $skin
     * @return bool
     */

    public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin)
    {
        $user = $out->getUser();
        $title = $out->getTitle();
        if ($user->isRegistered()) {
            self::logVisit($user, $title);
        }
        return true;
    }

    public static function onSkinAfterContent(&$data, Skin $skin)
    {
        global $wgMessagesDirs, $wgUserVisitLogger;
        $wgUserVisitLogger['show_count'];
        $wgMessagesDirs['UserVisitLogger'] = __DIR__ . '/i18n';
        wfMessage('visitor')->text();
        $title = $skin->getTitle();
        if ($title instanceof Title) {
            $pageId = $title->getArticleID();
            $count = 0;

            $userListHtml = self::_renderVisitors($pageId, $wgUserVisitLogger['show_count'] ? true : false, $count);

            $data .= '<div class="page-view-users"> ';
            if ($count == 0) {
                $data .= $userListHtml . '</div>';
            } else if ($count == 1) {
                $data .= wfMessage('visitor')->text() . ': ' . $userListHtml . '</div>';
            } else {
                $data .= wfMessage('visitors')->text() . ': ' . $userListHtml . '</div>';
            }
            return true;
        } else {
            $data .= '<div class="page-view-users">' . wfMessage('no-valid-page-id')->text() . '</div>';
            return true;
        }
    }
    /**
     * Helper function to get visitors user name, and the length of the visitors.
     * @param int $pageId The Id of the visited page
     * @param bool $show_count The flag that controls the output shape
     * @param int $count The output of the size of the visitors array (passed by reference)
     * @return array
     */
    private static function _renderVisitors(int $pageId, $show_count, &$count)
    {
        $visitors = self::getVisitors($pageId);
        $count = count($visitors);
        $lang = MediaWikiServices::getInstance()->getContentLanguage();
        if (empty($visitors)) {
            return wfMessage('no-visit');
        }
        if ($show_count) {
            $data = [];
            foreach ($visitors as $v) {
                $data[] = $v['username'] . ' (' . $lang->formatNum($v['count']) . ')';
            }
            return implode(', ', $data);
        }
        $data = [];
        foreach ($visitors as $v) {
            $data[] = $v['username'];
        }
        return implode(', ', $data);
    }




    /**
     * Log the user's visit to the page.
     * @param User $user
     * @param Title $title
     */
    private static function logVisit(User $user, Title $title)
    {
        $dbr = wfGetDB(DB_MASTER);
        $currentDate = wfTimestamp(TS_DB, wfTimestampNow());
        $currentDay = substr($currentDate, 0, 10);

        $userId = $user->getId();
        $pageId = $title->getArticleID();


        $row = $dbr->selectRow(
            'user_visit_log',
            ['count_per_day'],
            [
                'user_id' => $userId,
                'page_id' => $pageId,
                'visit_day' => $currentDay
            ],
            __METHOD__
        );

        if ($row) {
            $dbr->update(
                'user_visit_log',
                ['count_per_day = count_per_day + 1'],
                [
                    'user_id' => $userId,
                    'page_id' => $pageId,
                    'visit_day' => $currentDay
                ],
                __METHOD__
            );
        } else {
            $dbr->insert(
                'user_visit_log',
                [
                    'user_id' => $userId,
                    'page_id' => $pageId,
                    'visit_day' => $currentDay,
                    'count_per_day' => 1
                ],
                __METHOD__
            );
        }
    }

    /**
     * Get the username from user_id using UserFactory.
     *
     * @param int $userId The user ID.
     * @return string|null The username or null if not found.
     */
    public static function getLinkUsernameFromUserId($linkRenderer, $userId)
    {
        $userFactory = MediaWikiServices::getInstance()->getUserFactory();
        $user = $userFactory->newFromId($userId);
        if ($user && !$user->isAnon()) {
            $userPage = $user->getUserPage();
            return $linkRenderer->makeLink($userPage, htmlspecialchars($user->getName()));
        }

        return null;
    }

    /**
     * Get the list of users who visited the page.
     * @param int $page_id
     * @return array
     */
    public static function getVisitors(int $page_id)
    {
        $dbr = wfGetDB(DB_REPLICA);
        $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
        $visitors = [];
        $res = $dbr->select(
            'user_visit_log',
            ['user_id', 'sum(count_per_day) as count'],
            ['page_id' => $page_id],
            __METHOD__,
            ['ORDER BY' => 'visit_day DESC', 'GROUP BY' => 'user_id']
        );

        foreach ($res as $row) {
            $username = self::getLinkUsernameFromUserId($linkRenderer, $row->user_id);
            $visitors[] = array(
                'username' => $username,
                'count' => $row->count
            );
        }

        return $visitors;
    }
}
