Fones of the moon

This is the repository for the fones of the moon setup and configuration

A lot is still tbd


generating sounds
To generate sounds run this command
ffmpeg -i input.mp3 -ar 8000 -ac 1 -acodec pcm_s16le output.wav
then copy to the asterisk/sounds directory and reference in the files