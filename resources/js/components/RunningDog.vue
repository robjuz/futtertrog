<template>
    <div class="progress">
        <div class="progress-icons d-flex align-items-center">
            <font-awesome-icon icon="dog" size="3x" fixed-width class="progress-dog"/>
            <font-awesome-icon icon="bone" size="2x" fixed-width class="progress-bone ml-3"/>
        </div>

    </div>
</template>

<script>
  export default {
    name: 'RunningDog',
  };
</script>

<style scoped>

    .progress {
        position: relative;
        height: 3.5em;
        overflow: hidden;
    }

    .progress-icons {
        position: absolute;
        top: 0;
        bottom: 0;

        animation: run 12s linear infinite,
        flip-horizontal 12s linear infinite;
    }

    .progress-dog {
        animation: jump 300ms linear infinite;
    }

    .progress-bone {
        animation: rotate 300ms linear infinite;
    }

    @keyframes run {
        0%, 100% {
            left: -120px;
        }
        50% {
            left: calc(100% + 120px);
        }
    }

    @keyframes flip-horizontal {
        0%, 49%, 100% {
            transform: scale(1, 1);
        }
        50%, 99% {
            transform: scale(-1, 1);
        }
    }

    @keyframes jump {
        0%, 100% {
            transform: translateY(0)
        }
        50% {
            transform: translateY(-5px)
        }
    }

    @keyframes rotate {
        0%, 100% {
            transform: rotateZ(-15deg)
        }
        50% {
            transform: rotateZ(15deg)
        }
    }

</style>