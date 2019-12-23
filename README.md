# Vananaz Video Subtitler

## Project setup
```
composer install
See ffmpeg site for installation (https://www.ffmpeg.org/).
```
## Steps on How to use

1. Click the browse button and select the file that you want.
1. Click the submit button to send the file to the server.
1. Click the start button to start the extraction of text and translation. Wait for the progress bar to reach 100%.
1. Click the process button when the progress bar reach 100%.
1. Click the download button.

## Functions

* VideoCaptionController.php
* home.blade.php

# Back-end

### Process uploading of video
```
uploadVideo()
```
### Process Video Subtitling
```
processVideo()
```
### Download File
```
downloadFile()
```
# Front-end 

### Upload file to server 
```
const fileUpload = (e, audio)
```
### Start capturing the Audio 
```
startAudio.click
```
### Stop capturing the Audio
```
stopAudio.click
```
### Start processing the video
```
processButton.click
processVideo(e, subtitleArray, fileName)
```
### Get the subtitle time
```
const getSubtitleTime = (time, delay = null)
```
### Translate the text
```
const translate = async (sText)
```


