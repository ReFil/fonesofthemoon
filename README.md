# Fones of the moon

This is the repository for the fones of the moon setup and configuration

A lot is still tbd and honestly the more i do the ore ashamed I am of the state of things

1. Finish the manager - allow one hit button to purge all event registed phones
2. Setup asterisk AGI script to call external endpoints as well in groups
3. Add external endpoint to asterisk
4. Setup call proxying for asterisks external endpoint in the main dialplan
5. Asterisk voicemail?
2. Add OMM

## DB

The database is the ground source of info used by kamailio (and by extension OMM / the dect network) and asterisk for group calls. 

## Kamailio

Kamailio is the SIP server managing all the connections. For either a dect 

## Asterisk

Asterisk manages the moon group calling for now, but maybe it will be tweaked in the fitire to do more fun things e.g. games




### generating sounds
To generate sounds for playback run this command
ffmpeg -i input.mp3 -ar 8000 -ac 1 -acodec pcm_s16le output.wav
then copy to the asterisk/sounds directory and reference in the conf file(s)

## Deploying it yourself

Uhh good luck honestly, this is rapidly spiralling out of control and I keep bolting new bits on and forgetting what i'm doing. This will be properly documented at some point. 

## Problems yet to solve

Some but not all of the wireguard clients dont get their IP correctly masqueraded by the server and so RTP only goes in one direction

Going to a DECT RFP this doesnt work
```
16:04:58.171044 IP (tos 0xb8, ttl 64, id 18429, offset 0, flags [DF], proto UDP (17), length 200)
    192.168.1.14.16338 > 10.8.0.5.65442: UDP, length 172
16:04:58.188787 IP (tos 0x0, ttl 127, id 33016, offset 0, flags [none], proto UDP (17), length 200)
    charizard.localdomain.65442 > 192.168.1.14.16338: UDP, length 172
```

But calling from a different device connected to the same qireguard endopoint this happens and it works fin4e, and I'm unsure why
```
16:07:16.241485 IP (tos 0xb8, ttl 64, id 27334, offset 0, flags [DF], proto UDP (17), length 200)
    192.168.1.14.16344 > 10.8.0.12.41787: UDP, length 172
16:07:16.250190 IP (tos 0x0, ttl 63, id 1951, offset 0, flags [DF], proto UDP (17), length 200)
    10.8.0.12.41787 > 192.168.1.14.16344: UDP, length 172
```

This may have been wireguard doing NAT on some connections but not others, even though it really shouldn't mix them both, may be fixed now.