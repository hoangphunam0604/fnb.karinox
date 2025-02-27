<template>
  <div>
    <header>
      <h1>POS System</h1>
    </header>
    <main>
      <slot />
    </main>
  </div>
</template>

<style scoped>
header {
  background-color: #333;
  color: white;
  padding: 10px;
  text-align: center;
}
</style>
