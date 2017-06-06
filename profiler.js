const argv    = require('yargs').argv
const axios   = require('axios')
const chalk   = require('chalk')
const moment  = require('moment')
const Promise = require('bluebird')
const override = hasArgs => (hasArgs ? '&' : '?') + 'MOCK=true'

const recurse = (endpoint, recursionCount, success, fail) => {
  console.log(chalk.yellow(recursionCount))
  if(recursionCount === 0 && success) return success()
  axios.get(endpoint)
    .then(() => {
      recurse(endpoint, recursionCount - 1, success, fail)
    })
    .catch(err => {
      fail(err, recursionCount)
    })
}

const profile = (endpoint, count) => {
  let start = moment().valueOf()
  let recursion = count
  return new Promise((resolve, reject) => {
    console.log(chalk.white(`Starting profile of ${endpoint}`))
    recurse(endpoint, recursion, () => {
      let end = moment().valueOf()
      let duration = end - start
      console.log(chalk.blue('Elapsed time:') + chalk.white(duration/1000 + 's'))
      console.log(chalk.magenta('Average time:') + chalk.white(duration/recursion + 'ms'))
      resolve(duration)
    }, (err, recursionCount) => {
      console.log(chalk.red(`Error after ${count - recursionCount} iterations:\n\n${err}`))
      reject(err)
    })
  })
}

const compare = (a, b, count) => {
  let recursion = count
  profile(a, count)
    .then(durationA => {
      profile(b, count)
        .then(durationB => {
          let max = durationA < durationB ? durationB : durationA
          let min = durationA < durationB ? durationA : durationB
          let winner = max === durationA ? b : a
          console.log(chalk.green(winner + ' wins'))
          console.log(chalk.white(((max/min)-1).toFixed(4) + '% faster'))
        })
        .catch(() => {{}})
    })
    .catch(() => {})
}

if(!argv.endpoint){
  console.log(chalk.red('Please specify an endpoint to benchmark with --endpoint='))
} else {
  let endpoint = argv.endpoint
  let hasArgs  = endpoint.includes('?')
  compare(endpoint, endpoint + override(hasArgs), argv.count || 100)
}
