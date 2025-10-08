# spotify-AP

### fichier .env 

```
###> symfony/framework-bundle ###
APP_ENV=prod
APP_SECRET=***
###< symfony/framework-bundle ###

CLIENT_ID= # client id spotify
CLIENT_SECRET= # client id spotify

TOKEN_FILE=$HOME/public_html/spotify/token_file.json
PLAYLIST_ID= # l'id de la playlist à mettre à jour

SPOTIFY_CALLBACK_URI=http://spotify.guillaume-rousselet.com/callback
```

### déploiement 

`docker build -t spotify-ap .`
`docker run -d --rm -p 8888:80 --name spotify-ap2 spotify-ap`