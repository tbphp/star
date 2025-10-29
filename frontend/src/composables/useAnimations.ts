import { animate } from 'animejs'

export const useAnimations = () => {
  // Star flying animation (add stars)
  const flyStarIn = (fromElement: HTMLElement, toElement: HTMLElement) => {
    const star = document.createElement('div')
    star.textContent = '⭐'
    star.style.position = 'fixed'
    star.style.fontSize = '32px'
    star.style.zIndex = '9999'
    star.style.pointerEvents = 'none'

    const fromRect = fromElement.getBoundingClientRect()
    const toRect = toElement.getBoundingClientRect()

    star.style.left = `${fromRect.left + fromRect.width / 2}px`
    star.style.top = `${fromRect.top + fromRect.height / 2}px`

    document.body.appendChild(star)

    animate(star, {
      left: toRect.left + toRect.width / 2,
      top: toRect.top + toRect.height / 2,
      scale: [1, 0.5],
      rotate: 360,
      opacity: [1, 0],
      duration: 800,
      ease: 'inOut(quad)',
      complete: () => {
        document.body.removeChild(star)
      },
    })
  }

  // Star flying out animation (subtract stars)
  const flyStarOut = (fromElement: HTMLElement) => {
    const particleCount = 5
    for (let i = 0; i < particleCount; i++) {
      const particle = document.createElement('div')
      particle.textContent = '⭐'
      particle.style.position = 'fixed'
      particle.style.fontSize = '24px'
      particle.style.zIndex = '9999'
      particle.style.pointerEvents = 'none'

      const fromRect = fromElement.getBoundingClientRect()
      particle.style.left = `${fromRect.left + fromRect.width / 2}px`
      particle.style.top = `${fromRect.top + fromRect.height / 2}px`

      document.body.appendChild(particle)

      const angle = (360 / particleCount) * i
      const distance = 100

      animate(particle, {
        translateX: Math.cos((angle * Math.PI) / 180) * distance,
        translateY: Math.sin((angle * Math.PI) / 180) * distance,
        scale: [1, 0],
        opacity: [1, 0],
        duration: 600,
        delay: i * 50,
        ease: 'out(quad)',
        complete: () => {
          document.body.removeChild(particle)
        },
      })
    }
  }

  // Number count-up animation
  const countUp = (element: HTMLElement, from: number, to: number, duration = 500) => {
    const obj = { value: from }
    animate(obj, {
      value: to,
      duration,
      ease: 'out(quad)',
      update: () => {
        element.textContent = Math.floor(obj.value).toString()
      },
    })
  }

  // Fireworks animation for reward redemption
  const fireworks = (element: HTMLElement) => {
    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8']
    const particleCount = 30

    for (let i = 0; i < particleCount; i++) {
      const particle = document.createElement('div')
      particle.style.position = 'absolute'
      particle.style.width = '10px'
      particle.style.height = '10px'
      particle.style.borderRadius = '50%'
      particle.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)] || '#FF6B6B'
      particle.style.left = '50%'
      particle.style.top = '50%'
      particle.style.pointerEvents = 'none'

      element.appendChild(particle)

      const angle = (360 / particleCount) * i
      const distance = 150 + Math.random() * 50

      animate(particle, {
        translateX: Math.cos((angle * Math.PI) / 180) * distance,
        translateY: Math.sin((angle * Math.PI) / 180) * distance,
        scale: [0, 1, 0],
        opacity: [1, 1, 0],
        duration: 1000 + Math.random() * 500,
        delay: i * 20,
        ease: 'out(expo)',
        complete: () => {
          element.removeChild(particle)
        },
      })
    }
  }

  // Card flip animation
  const flipCard = (element: HTMLElement) => {
    animate(element, {
      rotateY: [0, 180],
      duration: 600,
      ease: 'inOut(quad)',
    })
  }

  // Shake animation (for errors)
  const shake = (element: HTMLElement) => {
    animate(element, {
      translateX: [
        { value: -10, duration: 100 },
        { value: 10, duration: 100 },
        { value: -10, duration: 100 },
        { value: 10, duration: 100 },
        { value: 0, duration: 100 },
      ],
      ease: 'inOut(quad)',
    })
  }

  // Bounce animation (for success)
  const bounce = (element: HTMLElement) => {
    animate(element, {
      scale: [
        { value: 1.1, duration: 200 },
        { value: 0.9, duration: 200 },
        { value: 1.05, duration: 200 },
        { value: 1, duration: 200 },
      ],
      ease: 'inOut(quad)',
    })
  }

  // Pulse animation (for highlighting)
  const pulse = (element: HTMLElement) => {
    animate(element, {
      scale: [1, 1.1, 1],
      duration: 600,
      loop: 3,
      ease: 'inOut(quad)',
    })
  }

  return {
    flyStarIn,
    flyStarOut,
    countUp,
    fireworks,
    flipCard,
    shake,
    bounce,
    pulse,
  }
}

