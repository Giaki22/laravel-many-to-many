<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Post;
use Illuminate\Http\Request;
use App\Category;
use App\Tag;

class PostController extends Controller
{
    private $validation = [
        'title' => 'required|string|max:255',
        'content' => 'required|string|max:65535',
        'published' => 'sometimes|accepted',
        'category_id' => 'nullable|exists:categories,id',
        'tags' => 'nullable|exists:tags,id',
    ];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Post::all();
        return view('admin.posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::all();
        $tags = Tag::all();
        return view('admin.posts.create', compact('categories','tags'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate($this->validation);
        $data = $request->all();
        $newPost = new Post();
        $newPost->fill($data);
        $newPost->slug = $this->getSlug($data['title']);
        $newPost->published = isset($data['published']);
        $newPost->save();

        if(isset($data['tags'])) {
            $newPost->tags()->sync($data['tags']);
        }

        return redirect()->route('admin.posts.show', $newPost->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        return view('admin.posts.show', compact('post'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        $categories = Category::all();
        $tags = Tag::all();
        $postTags = $post->tags->map(function ($tag) {
            return $tag->id;
        })->toArray();
        return view('admin.posts.edit', compact('post','categories','tags','postTags'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        $request->validate($this->validation);
        $data = $request->all();
        if($post->title != $data['title']){
            $post->slug = $this->getSlug($data['title']);
        }
        $post->fill($data);
        $post->published = isset($data['published']);
        $post->save();
        $tags = isset($data['tags']) ? $data['tags'] : [];
        $post->tags()->sync($tags);
        return redirect()->route('admin.posts.show', $post->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        $post->delete();
        return redirect()->route('admin.posts.index');
    }
    private function getSlug($title)
    {
        $slug = Str::of($title)->slug('-');
        $count = 1;

        while( Post::where('slug', $slug)->first() ) {
            $slug = Str::of($title)->slug('-') . "-{$count}";
            $count++;
        }

        return $slug;
    }
}
