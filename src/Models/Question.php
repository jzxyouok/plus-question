<?php

namespace SlimKit\PlusQuestion\Models;

use Zhiyi\Plus\Models\User;
use Zhiyi\Plus\Models\Comment;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['pivot'];

    /**
     * Has topics for the question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|null
     * @author Seven Du <shiweidu@outlook.com>
     */
    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'question_topic')
            ->using(QuestionTopic::class);
    }

    /**
     * Has invitation users for the question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|null
     * @author Seven Du <shiweidu@outlook.com>
     */
    public function invitations()
    {
        return $this->belongsToMany(User::class, 'question_invitation');
    }

    /**
     * Has answers for the question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|null
     * @author Seven Du <shiweidu@outlook.com>
     */
    public function answers()
    {
        return $this->hasMany(Answer::class, 'question_id', 'id');
    }

    /**
     * Has the user for question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author Seven Du <shiweidu@outlook.com>
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Has watch users for the question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|null
     * @author Seven Du <shiweidu@outlook.com>
     */
    public function watchers()
    {
        return $this->belongsToMany(User::class, 'question_watcher')
            ->using(QuestionWatcher::class)
            ->withTimestamps();
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * A question may have one or more applications.
     *
     * @author bs<414606094@qq.com>
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|null
     */
    public function applications()
    {
        return $this->hasMany(QuestionApplication::class, 'question_id', 'id');
    }
}
