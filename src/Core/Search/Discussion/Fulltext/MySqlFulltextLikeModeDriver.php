<?php
/*
 * 添加 Flarum 对中文搜索的支持
 *
 * Flarum 的搜索默认使用了 MySQL 的全文检索。
 * 但是 MySQL 的全文检索功能默认不支持中文。
 * 本方案把 Flarum 的搜索改成了基于 LIKE 语句的 SQL 搜索。
 *
 * @author justjavac <justjavac@gmail.com>
 */

namespace Flarum\Core\Search\Discussion\Fulltext;

use Flarum\Core\Post;
use Flarum\Core\Discussion;

class MySqlFulltextLikeModeDriver implements DriverInterface
{
    /**
     * {@inheritdoc}
     */
    public function match($string)
    {
        $relevantPostIds = [];
        $discussionIds = Discussion::where('title', 'like', '%'.$string.'%')->lists('id');

        foreach ($discussionIds as $discussionId) {
            $relevantPostIds[$discussionId] = [];
        }

        $discussionIds = preg_match('/^[\w\s]+$/',$string) ?
            Post::where('type', 'comment')
            ->whereRaw('MATCH (`content`) AGAINST (? IN BOOLEAN MODE)', [$string])
            ->orderByRaw('MATCH (`content`) AGAINST (?) DESC', [$string])
            ->lists('discussion_id', 'id') : 
            Post::where('type', 'comment')
            ->where('content', 'like', '%'.$string.'%')
            ->lists('discussion_id', 'id');

        foreach ($discussionIds as $postId => $discussionId) {
            $relevantPostIds[$discussionId][] = $postId;
        }

        return $relevantPostIds;
    }
}
