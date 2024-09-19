# Podcast Share

Is a simple platform that allow to listen a RSS Feed Podcast with other people

U can listen, react and chat on real time.

![mainpage](https://github.com/user-attachments/assets/c2943558-d7c8-4796-a448-5a48a7fc9777)

![showpage](https://github.com/user-attachments/assets/aaa0b65e-6369-43ff-a8b9-4f3a12724995)


#### How does it work

First we create a PartyListening by providing the Name, RRS Feed and the time when we want to start. By time the
PartyListening its create a background job its running to get the all the episode other info for that Party.

When the PartyListening is created we can share the link with other people and they can join the party.

Using Reverb we can listen the podcast in sync with other people, we can react to the podcast and chat in real time.

#### How to install

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
```

For running the server Node, Queue, Scheduler and Reverb need to be running

```bash
php artisan serve
```

```bash
npm run dev
```

```bash
php artisan queue:work
```

```bash
php artisan schedule:work
```

```bash
php artisan reverb:run
```



