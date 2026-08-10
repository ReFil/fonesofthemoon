# Fones of the moon

This is the repository for the fones of the moon setup and configuration

A lot is still tbd

1. Theme the manager
2. Setup and document asterisk
4. Add OMM
3. remove pgadmin if manager works
4. Create a new branch for pinging an external phones system e.g. at events

## DB

The database is the ground source of info used by kamailio (and by extension OMM / the dect network) and asterisk for group calls. 

## Kamailio

Kamailio is the SIP server managing all the connections. For either a dect 

## Asterisk

Asterisk manages the moon group calling for now, but maybe it will be tweaked in the fitire




### generating sounds
To generate sounds for playback run this command
ffmpeg -i input.mp3 -ar 8000 -ac 1 -acodec pcm_s16le output.wav
then copy to the asterisk/sounds directory and reference in the conf file(s)