<?php

namespace SlimKit\PlusQuestion\API2\Controllers;

use Illuminate\Http\Request;
use Zhiyi\Plus\Models\Comment;
use SlimKit\PlusQuestion\API2\Requests\CommentRequest;
use SlimKit\PlusQuestion\Models\Answer as AnswerModel;
use SlimKit\PlusQuestion\Models\Question as QuestionModel;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;

class CommentController extends Controller
{
    /**
     * 问题评论列表.
     */
    public function questionComments(Request $request, QuestionModel $question, ResponseFactoryContract $response)
    {
        $after = $request->input('after');
        $limit = $request->input('limit', 20);
        $comments = $question->comments()
            ->when($after, function ($query) use ($after) {
                $query->where('id', '<', $after);
            })
        ->orderBy('id', 'desc')
        ->limit($limit)
        ->get();

        return $response->json($comments)
            ->setStatusCode(200);
    }

    /**
     * 问题回答评论.
     */
    public function answerComments(Request $request, AnswerModel $answer, ResponseFactoryContract $response)
    {
        $after = $request->input('after');
        $limit = $request->input('limit', 20);
        $comments = $answer->comments()
            ->when($after, function ($query) use ($after) {
                $query->where('id', '<', $after);
            })
        ->orderBy('id', 'desc')
        ->limit($limit)
        ->get();

        return $response->json($comments)
            ->setStatusCode(200);
    }

    /**
     * 存储问题评论.
     */
    public function storeQuestionComment(CommentRequest $request, QuestionModel $question, Comment $comment)
    {
        $replyUser = intval($request->input('reply_user', 0));
        $body = $request->input('body');
        $user = $request->user();

        $comment->user_id = $user->id;
        $comment->target_user = $question->user_id;
        $comment->reply_user = $replyUser;
        $comment->body = $body;

        $question->getConnection()->transaction(function () use ($question, $comment, $user) {
            $question->comments()->save($comment);
            $question->increment('comments_count', 1);
            $user->extra()->firstOrCreate([])->increment('comments_count', 1);
        });

        $question->user->sendNotifyMessage('question:comment', sprintf('%s评论了你的问题', $user->name), [
                'question' => $question,
                'user' => $user,
            ]);

        if ($replyUser) {
            $replyUser = $user->newQuery()->where('id', $replyUser)->first();
            $message = sprintf('%s 回复了您的评论', $user->name);
            $replyUser->sendNotifyMessage('question:comment-reply', $message, [
                'question' => $question,
                'user' => $user,
            ]);
        }

        return response()->json([
            'message' => ['操作成功'],
            'comment' => $comment,
        ])->setStatusCode(201);
    }

    /**
     * 存储回答评论.
     */
    public function storeAnswerComment(CommentRequest $request, AnswerModel $answer, Comment $comment)
    {
        $replyUser = intval($request->input('reply_user', 0));
        $body = $request->input('body');
        $user = $request->user();

        $comment->user_id = $user->id;
        $comment->target_user = $answer->user_id;
        $comment->reply_user = $replyUser;
        $comment->body = $body;

        $answer->getConnection()->transaction(function () use ($comment, $user, $answer) {
            $answer->comments()->save($comment);
            $answer->increment('comments_count', 1);
            $user->extra()->firstOrCreate([])->increment('comments_count', 1);
        });

        // 通知回答问题的用户
        $answer->user->sendNotifyMessage('answer:comment', sprintf('%s评论了你的回答', $user->name), [
                'answer' => $answer,
                'user' => $user,
            ]);

        // 通知被回复的用户
        if ($replyUser) {
            $replyUser = $user->newQuery()->where('id', $replyUser)->first();
            $message = sprintf('%s 回复了您的评论', $user->name);
            $replyUser->sendNotifyMessage('answer:comment-reply', $message, [
                'answer' => $answer,
                'user' => $user,
            ]);
        }

        // 被回复用户不是问题发起者
        if ($replyUser && $answer->question->user_id !== $replyUser) {
            $questionOwner = $user->newQuery()->where('id', $answer->question->user_id)->first();
            $message = '您的问题有新的评论';
            $questionOwner->sendNotifyMessage('answer:comment-reply', $message, [
                'answer' => $answer,
                'user' => $user,
            ]);
        }

        return response()->json([
            'message' => ['操作成功'],
            'comment' => $comment,
        ])->setStatusCode(201);
    }

    /**
     * delete a comment of a question.
     *
     * @author bs<414606094@qq.com>
     * @param  Illuminate\Http\Request $request
     * @param  SlimKit\PlusQuestion\Models\Question $question
     * @param  Zhiyi\Plus\Models\Comment $comment
     * @return mixed
     */
    public function delQuestionComment(Request $request, QuestionModel $question, Comment $comment)
    {
        $user = $request->user();
        if ($user->id !== $comment->user_id) {
            return response()->json(['message' => [trans('plus-question::comments.not-owner')]], 403);
        }

        $question->getConnection()->transaction(function () use ($question, $comment, $user) {
            $comment->delete();
            $question->decrement('comments_count', 1);
            $user->extra()->decrement('comments_count', 1);
        });

        return response()->json()->setStatusCode(204);
    }

    /**
     * delete a comment of an answer.
     *
     * @author bs<414606094@qq.com>
     * @param  Illuminate\Http\Request $request
     * @param  SlimKit\PlusQuestion\Models\Answer $answer
     * @param  Zhiyi\Plus\Models\Comment $comment
     * @return mixed
     */
    public function delAnswerComment(Request $request, AnswerModel $answer, Comment $comment)
    {
        $user = $request->user();
        if ($user->id !== $comment->user_id) {
            return response()->json(['message' => [trans('plus-question::comments.not-owner')]], 403);
        }

        $answer->getConnection()->transaction(function () use ($answer, $comment, $user) {
            $comment->delete();
            $answer->decrement('comments_count', 1);
            $user->extra()->decrement('comments_count', 1);
        });

        return response()->json()->setStatusCode(204);
    }
}
