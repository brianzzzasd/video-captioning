<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
          <form method="POST" enctype="multipart/form-data" id="fileUploadForm">
              <?php echo csrf_field(); ?>
              <label class="file-label">
                <i class="fa fa-cloud-upload" aria-hidden="true"></i> Browse
                <input class="d-none" type="file" name="video" id="input">
              </label>
              <input class="btn btn-success float-right" type="submit" name="Upload" id="upload" disabled>
          </form>
            <div class="mt-3">
              <button class="btn btn-info text-white play" disabled>Start</button>
              <button class="btn btn-danger text-white stop" disabled>Stop</button>
              <button class="btn btn-secondary process float-right" disabled>Process</button>
            </div>

            <div class="mt-3">
              <div class="alert alert-success fade d-none" role="alert">
                Subtitle extracted!
              </div>
               <div class="progress">
                  <div class="progress-bar" role="progressbar" style="background: #F7C717" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">
                </div>
              </div>
              <div class="spinning-loader mt-3 d-none">
                <div class="loading-title">Processing please wait . . </div>
                <div class="spinner-border mt-3" role="status">
                  <span class="sr-only">Loading...</span>
                </div>
              </div>
              <div class="file-link d-none flex-column mt-3 justify-content-center text-center">
                  <form action="/downloadFile" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="fileName" value="" id="download-file-name">
                    <button type="submit" class="btn btn-info text-white"><i class="fa fa-cloud-download" aria-hidden="true"></i> Click here to download</button>
                  </form>
              </div>
            </div>
        </div>
    </div>
</div>
<script type="application/javascript">
//csrf token 
const csrfToken = $('meta[name=csrf-token]').attr('content')

// Web APIs
const recognition = new webkitSpeechRecognition() // Webkit on Play
const audio = new Audio()
recognition.lang = 'ja'

// Buttons
const startAudio = $('.play')
const stopAudio = $('.stop')
const processButton = $('.process')

let fileName = '' // File name default empty
let processedFileName = ''
let subtitleOrder = 1 // To keep track of the subtitle order
let subtitleArray = [] // Where subtitle data is stored

// Might delete it later
let speechesCount = 0 // Count Speech instances
let speechArray = [] // Speech pushed

// Previous setence ending milliseconds
let previousMili = 0
let speechStart = null

let currentSeconds = 0

let newSentence = false
let nextSentenceTime = null

recognition.interimResults = true

recognition.addEventListener('result', async (e) => {
  const executionTime = currentSeconds

  const transcript = Array.from(e.results) 
      .map(result => result[0])
      .map(result => result.transcript)
      .join('')

  if (!speechStart) {
    speechStart = getSubtitleTime(executionTime)
  }

  if (newSentence) {
    nextSentenceTime = getSubtitleTime(executionTime, 2)
    newSentence = false
  }

  speechStart = speechStart || (getSubtitleTime(executionTime))

  if (e.results[0].isFinal) {
      text = await translate(transcript)
      subtitleArray.push({
        order: subtitleOrder,
        text: text,
        japanese: transcript,
        start: subtitleOrder == 1 ? speechStart : nextSentenceTime,
        end: getSubtitleTime(executionTime),
      })

      console.log(subtitleArray)
      console.log(transcript)
      previousMili = executionTime
      subtitleOrder++
  }
})

$('#input').change(() => {
  $('#upload').prop('disabled', false)
})

recognition.addEventListener('end', function () {
  recognition.start()
  newSentence = true
})

startAudio.click(() => {
 let interval = setInterval(() => {
    currentSeconds++
    let duration = audio.duration + 2
    let percentage = parseInt((currentSeconds / duration) * 100)

    percentage = `${percentage > 100 ? 100 : percentage}%`

    $('.progress-bar').css('width', percentage).html(percentage)

    if (currentSeconds >= duration) {
      prompt = $('.alert-success')
      clearInterval(interval)

      setTimeout(() => {
        prompt.removeClass('d-none').addClass('show')

        setTimeout(() => {
          prompt.addClass('d-none').removeClass('show')
        }, 5000)
      }, 300)

      processButton.prop('disabled', false)
    }
  }, 1000)

  recognition.start()
  audio.play()
})

stopAudio.click(() => {
  recognition.stop()
  audio.pause()
  audio.currentTime = 0
})

$('#upload').click((e) => {
  fileUpload(e, audio)
})

processButton.click( async (e) => {
  processVideo(e, subtitleArray, fileName)
})

const fileUpload = (e, audio) => {
  e.preventDefault()

  let form = $('#fileUploadForm')[0]

  let data = new FormData(form)

  $.ajax({
      type: 'post',
      enctype: 'multipart/form-data',
      url: '/upload',
      data: data,
      processData: false,
      contentType: false,
      cache: false,
      timeout: 600000,
      success: (e) => {
        audio.src = e.media_path
        fileName = (e.media_path).replace('storage/', '')

        startAudio.prop('disabled', false)
        stopAudio.prop('disabled', false)
      },
      error: (e) => {

      }
  })
}

const getSubtitleTime = (time, delay = null) => {
  if (delay) {
    time = time - delay
  }

  seconds = Math.floor((time) % 60),
  minutes = Math.floor((time / (60)) % 60),
  hours = Math.floor((time / (60 * 60)) % 24);

  hours = (hours < 10) ? `0${hours}` : hours;
  minutes = (minutes < 10) ? `0${minutes}` : minutes;
  seconds = (seconds < 10) ? `0${seconds}` : seconds;

  return `${hours}:${minutes}:${seconds},000`
}

const processVideo = (e, subArray, fileName) => {
  if (subtitleArray.length > 0) {
    $('.spinning-loader').removeClass('d-none').addClass('d-flex')

    $.ajax({
      type: 'POST',
      url: '/processVideo',
      data: {
        _token: csrfToken,
        subtitles: subArray,
        fileName: fileName,
      },
      success: (e) => {
        processedFileName = e.hashed_file_name

        $('.spinning-loader').addClass('d-none').removeClass('d-flex')
        $('.file-link').removeClass('d-none').addClass('d-flex')
        $('#download-file-name').val(processedFileName)

        swal({
          title: "Successfully Subbed the video!",
          icon: "success",
        })

        startAudio.prop('disabled', true)
        stopAudio.prop('disabled', true)
      }
    })
  } else {
    swal({
      title: "No Subtitles were extracted",
      icon: "error",
    })
  }
}

const translate = async (sText) => {
  const key = 'trnsl.1.1.20191222T040718Z.dabb17432d3abf4a.951641b934f548ee2a3c5755aab417743765573d'

  const url = `https://translate.yandex.net/api/v1.5/tr.json/translate?key=${key}&text=${sText}&lang=ja-en`

  let response = await fetch(url)

  if (response.ok) {
    englishText = await response.json()

    return englishText.text[0]
  }
}

// const parseTranslate = async (subArray) => {
//   let fullText = ''
//   let translatedFullText = ''
//   let textArray = []

//   subArray.forEach((value, index) => {
//     fullText += value.text
//   })

//   translatedFullText = await translate(fullText)

//   textArray = translatedFullText.split(',')

//   textArray.forEach((value, index) => {
//     subArray[index].text = value.replace(',', '')
//   })

//   return subArray
// }

</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/briankentrepuesto/Sites/video-caption/auto-subtitle-app/resources/views/home.blade.php ENDPATH**/ ?>